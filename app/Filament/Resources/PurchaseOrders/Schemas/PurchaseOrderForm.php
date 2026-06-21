<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use App\Models\PurchaseOrder;
use App\Models\Item;
use App\Models\Pajak;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderForm
{
    public static function configure($form)
    {
        // Fungsi pembantu: Kunci form jika status data lama BUKAN 'draft'
        $isReadOnly = function ($get) {
            $status = $get('status');
            return $status && $status !== 'draft';
        };

        return $form
            ->schema([
                // Kiri 1
                TextInput::make('po_number')
                    ->label('Nomor PO')
                    ->default(function () {
                        $datePrefix = date('ymd');
                        $lastPO = PurchaseOrder::where('po_number', 'like', "PO-{$datePrefix}%")->latest('id')->first();
                        if (!$lastPO) return "PO-{$datePrefix}001";
                        $lastNumber = (int) substr($lastPO->po_number, -3);
                        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                        return "PO-{$datePrefix}" . $nextNumber;
                    })
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                // Kanan 1
                DatePicker::make('po_date')
                    ->label('Tanggal PO')
                    ->default(now())
                    ->disabled($isReadOnly)
                    ->required(),

                // Kiri 2
                Select::make('supplier_id')
                    ->label('Supplier / Vendor')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled($isReadOnly)
                    ->required(),

                // Kanan 2
                TextInput::make('shipping_cost')
                    ->label('Ongkos Kirim')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->live()
                    ->disabled($isReadOnly)
                    ->afterStateUpdated(function ($set, $get) {
                        self::updateGrandTotal($set, $get);
                    }),

                // Kiri 3
                Select::make('user_id')
                    ->label('Admin Pembuat')
                    ->default(Auth::id())
                    ->relationship('user', 'name')
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                // Kanan 3: Toggle PPN
                Toggle::make('is_ppn')
                    ->label('Apakah Transaksi Ini Kena PPN?')
                    ->formatStateUsing(function ($get, $state) {
                        if ((float)$get('ppn_percentage') > 0 || $get('pajak_id')) {
                            return true;
                        }
                        return $state ?? false;
                    })
                    ->live()
                    ->disabled($isReadOnly)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state) {
                            $pajakAktif = Pajak::where('status', true)->first();
                            if ($pajakAktif) {
                                $set('pajak_id', $pajakAktif->id);
                                $set('ppn_percentage', $pajakAktif->ppn);
                                $set('ppn_percentage_label', number_format($pajakAktif->ppn, 0) . '%');
                            } else {
                                $set('pajak_id', null);
                                $set('ppn_percentage', 0);
                                $set('ppn_percentage_label', '0% (Tidak ada pajak aktif)');
                            }
                        } else {
                            $set('pajak_id', null);
                            $set('ppn_percentage', 0);
                            $set('ppn_percentage_label', '0%');
                        }
                        self::updateGrandTotal($set, $get);
                    }),

                // Kanan 4: Besaran PPN Label
                TextInput::make('ppn_percentage_label')
                    ->label('Besaran PPN Yang Berlaku')
                    ->formatStateUsing(function ($get) {
                        $snapshotPersen = (float)$get('ppn_percentage');
                        if ($snapshotPersen > 0) {
                            return number_format($snapshotPersen, 0) . '%';
                        }
                        $pajakId = $get('pajak_id');
                        if ($pajakId) {
                            $pajak = Pajak::find($pajakId);
                            return $pajak ? number_format($pajak->ppn, 0) . '%' : '0%';
                        }
                        return '0%';
                    })
                    ->disabled()
                    ->dehydrated(false),

                Hidden::make('status')->default('draft')->dehydrated(),
                Hidden::make('pajak_id')->live(),

                // HIDDEN FIELD SNAPSHOT WITH HYDRATION
                Hidden::make('ppn_percentage')
                    ->live()
                    ->dehydrated()
                    ->afterStateHydrated(function ($set, $get, $state) {
                        $currentValue = (float)$state;
                        if ($currentValue === 0.00 && $get('pajak_id')) {
                            $pajak = Pajak::find($get('pajak_id'));
                            if ($pajak) {
                                $set('ppn_percentage', (float)$pajak->ppn);
                            }
                        }
                    }),

                // REPEATER DETAIL BARANG
                Repeater::make('details')
                    ->relationship()
                    ->schema([
                        Select::make('item_id')
                            ->label('Pilih Barang')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled($isReadOnly)
                            ->afterStateUpdated(function ($set, $get, $state, $statePath) {
                                $item = Item::find($state);
                                $price = 0;
                                $qty = $get('qty') ?? 1;
                                if ($item) {
                                    $price = $item->price;
                                    $set('price_at_purchase', $price);
                                    $set('subtotal', $price * $qty);
                                } else {
                                    $set('price_at_purchase', 0);
                                    $set('subtotal', 0);
                                }

                                $currentRowKey = null;
                                if (preg_match('/details\.([^.]+)/', $statePath, $matches)) {
                                    $currentRowKey = $matches[1];
                                }
                                self::updateGrandTotal($set, $get, $currentRowKey, [
                                    'qty' => $qty,
                                    'price_at_purchase' => $price,
                                ]);
                                
                            }),

                        TextInput::make('qty')
                            ->label('Jumlah (Qty)')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->disabled($isReadOnly)
                            ->afterStateUpdated(function ($set, $get, $state, $statePath) {
                                $price = $get('price_at_purchase') ?? 0;
                                $set('subtotal', $price * (int)$state);

                                $currentRowKey = null;
                                if (preg_match('/details\.([^.]+)/', $statePath, $matches)) {
                                    $currentRowKey = $matches[1];
                                }
                                self::updateGrandTotal($set, $get, $currentRowKey, [
                                    'qty' => (int)$state,
                                    'price_at_purchase' => $price,
                                ]);
                            }),

                        TextInput::make('price_at_purchase')
                            ->label('Harga Beli Bersih')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->disabled($isReadOnly)
                            ->afterStateUpdated(function ($set, $get, $state, $statePath) {
                                $qty = $get('qty') ?? 1;
                                $set('subtotal', (float)$state * $qty);

                                $currentRowKey = null;
                                if (preg_match('/details\.([^.]+)/', $statePath, $matches)) {
                                    $currentRowKey = $matches[1];
                                }
                                self::updateGrandTotal($set, $get, $currentRowKey, [
                                    'qty' => $qty,
                                    'price_at_purchase' => (float)$state,
                                ]);
                            }),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->createItemButtonLabel('Tambah Baris Barang Baru')
                    ->disableItemCreation($isReadOnly) // Blokir tambah baris jika bukan draft
                    ->disableItemDeletion($isReadOnly) // Blokir hapus baris jika bukan draft
                    ->disableItemMovement($isReadOnly) // Blokir geser baris jika bukan draft
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        self::updateGrandTotal($set, $get);
                    })
                    ->columnSpanFull(),

                // GRAND TOTAL KESELURUHAN
                TextInput::make('grand_total')
                    ->label('Grand Total Keseluruhan (Termasuk PPN & Ongkir)')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->extraAttributes(['style' => 'font-size: 1.2rem; font-weight: bold; color: #ff9800;'])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Kalkulasi Handal Berbasis Snapshot Data Historis
     */
    public static function updateGrandTotal($set, $get, $currentRowKey = null, $currentRowData = []): void
    {
        $details = $get('details') ?? $get('../../details') ?? [];
        $totalSubtotal = 0;

        foreach ($details as $key => $detail) {
            if ($currentRowKey !== null && (string)$key === (string)$currentRowKey) {
                $qty = (int)($currentRowData['qty'] ?? 0);
                $price = (float)($currentRowData['price_at_purchase'] ?? 0);
            } else {
                $qty = (int)($detail['qty'] ?? 0);
                $price = (float)($detail['price_at_purchase'] ?? 0);
            }
            $totalSubtotal += ($qty * $price);
        }

        $persenPajak = (float)($get('ppn_percentage') ?? $get('../../ppn_percentage') ?? 0);
        $pajakId = $get('pajak_id') ?? $get('../../pajak_id');

        if ($persenPajak == 0 && $pajakId) {
            $pajak = Pajak::find($pajakId);
            if ($pajak) {
                $persenPajak = (float)$pajak->ppn;
            }
        }

        $shippingCost = (float)($get('shipping_cost') ?? $get('../../shipping_cost') ?? 0);
        $nilaiPajak = $totalSubtotal * ($persenPajak / 100);
        $grandTotal = $totalSubtotal + $nilaiPajak + $shippingCost;

        $set('grand_total', $grandTotal);
        $set('../../grand_total', $grandTotal);
    }
}

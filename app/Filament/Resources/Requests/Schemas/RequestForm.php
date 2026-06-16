<?php

namespace App\Filament\Resources\Requests\Schemas;

use App\Models\Item;
use App\Models\Request;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $isReadOnly = function ($get) {
            $status = $get('status');
            return $status && $status !== 'pending';
        };

        return $schema
            ->components([
                TextInput::make('request_number')
                    ->label('Nomor Request')
                    ->default('[Otomatis]')
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Select::make('user_id')
                    ->label('Staf Peminta')
                    ->relationship('user', 'name')
                    ->default(Auth::id())
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Select::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->default(fn () => Auth::user()?->department_id)
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Select::make('unit_id')
                    ->label('Unit Kerja')
                    ->relationship('unit', 'name')
                    ->default(fn () => Auth::user()?->unit_id)
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                TextInput::make('status')
                    ->label('Status')
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),

                Textarea::make('notes')
                    ->label('Catatan / Keterangan')
                    ->disabled($isReadOnly)
                    ->columnSpanFull(),

                Repeater::make('details')
                    ->relationship('details')
                    ->schema([
                        Select::make('item_id')
                            ->label('Pilih Barang')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled($isReadOnly)
                            ->afterStateUpdated(function ($set, $get, $state) {
                                $item = Item::find($state);
                                if ($item) {
                                    $qty = (int)($get('qty_requested') ?? 1);
                                    $set('price_at_transaction', $item->price);
                                    $set('subtotal', $item->price * $qty);

                                    // Lakukan reservasi sementara untuk user ini
                                    $userId = Auth::id();
                                    if ($userId) {
                                        Request::reserveStockTemp($state, $userId, $qty);
                                    }
                                } else {
                                    $set('price_at_transaction', 0.00);
                                    $set('subtotal', 0.00);
                                }
                                self::updateTotalAmount($set, $get);
                            }),

                        TextInput::make('qty_requested')
                            ->label('Jumlah Permintaan')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->disabled($isReadOnly)
                            ->afterStateUpdated(function ($set, $get, $state) {
                                $itemId = $get('item_id');
                                if ($itemId) {
                                    $item = Item::find($itemId);
                                    if ($item) {
                                        $set('price_at_transaction', $item->price);
                                        $set('subtotal', $item->price * (int)$state);

                                        // Lakukan reservasi sementara untuk user ini
                                        $userId = Auth::id();
                                        if ($userId) {
                                            Request::reserveStockTemp($itemId, $userId, (int)$state);
                                        }
                                    }
                                }
                                self::updateTotalAmount($set, $get);
                            })
                            ->helperText(function ($get, $state, $livewire) {
                                $itemId = $get('item_id');
                                if (!$itemId) return null;
                                
                                $item = Item::find($itemId);
                                if (!$item) return null;

                                // Di dalam Repeater, $record adalah child (RequestDetail).
                                // Ambil parent record ID dari $livewire->record
                                $excludeRequestId = isset($livewire->record) ? $livewire->record->id : null;
                                $userId = Auth::id();
                                $availableStock = Request::getAvailableStock($itemId, $excludeRequestId, $userId);
                                
                                $qty = (int)$state;
                                if ($qty > $availableStock) {
                                    return new \Illuminate\Support\HtmlString(
                                        "<span style='color: #ef4444; font-weight: 500; display: inline-block; margin-top: 0.25rem;'>" .
                                        "⚠️ Jumlah melebihi stok tersedia! Maksimal yang dapat diminta saat ini adalah {$availableStock} {$item->unit}." .
                                        "</span>"
                                    );
                                }
                                
                                return "Stok tersedia untuk diminta saat ini: {$availableStock} {$item->unit}.";
                            }),

                        TextInput::make('price_at_transaction')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00),

                        TextInput::make('subtotal')
                            ->label('Subtotal (Estimasi)')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->createItemButtonLabel('Tambah Barang')
                    ->disableItemCreation($isReadOnly)
                    ->disableItemDeletion($isReadOnly)
                    ->disableItemMovement($isReadOnly)
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        self::updateTotalAmount($set, $get);
                    })
                    ->columnSpanFull(),

                TextInput::make('total_amount')
                    ->label('Total Estimasi Permintaan')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->default(0.00)
                    ->extraAttributes(['style' => 'font-size: 1.2rem; font-weight: bold; color: #ff9800;'])
                    ->columnSpanFull(),
            ]);
    }

    public static function updateTotalAmount($set, $get): void
    {
        $details = $get('details') ?? [];
        $total = 0;

        foreach ($details as $detail) {
            $qty = (int)($detail['qty_requested'] ?? 0);
            $price = (float)($detail['price_at_transaction'] ?? 0);
            $total += ($qty * $price);
        }

        $set('total_amount', $total);
    }
}

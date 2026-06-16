<?php

namespace App\Filament\Resources\AdjustmentRequests\Schemas;

use App\Models\AdjustmentRequest;
use App\Models\Item;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AdjustmentRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $isReadOnly = function ($get) {
            $status = $get('status');
            return $status && $status !== 'pending';
        };

        return $schema
            ->components([
                TextInput::make('adjustment_number')
                    ->label('Nomor Adjustment')
                    ->default(function () {
                        $year = date('Y');
                        $lastAdj = AdjustmentRequest::where('adjustment_number', 'like', "ADJ-{$year}-%")->latest('id')->first();
                        if (!$lastAdj) return "ADJ-{$year}-0001";
                        $lastNumber = (int) substr($lastAdj->adjustment_number, -4);
                        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                        return "ADJ-{$year}-" . $nextNumber;
                    })
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Select::make('user_id')
                    ->label('Admin Gudang Pengaju')
                    ->relationship('user', 'name')
                    ->default(Auth::id())
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Select::make('item_id')
                    ->label('Pilih Barang')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->disabled($isReadOnly)
                    ->afterStateUpdated(function ($set, $state) {
                        $item = Item::find($state);
                        if ($item) {
                            $set('current_stock_at_request', $item->stock);
                        } else {
                            $set('current_stock_at_request', 0);
                        }
                    }),

                Select::make('type')
                    ->label('Jenis Penyesuaian')
                    ->options([
                        'in' => 'Tambah Stok (Masuk)',
                        'out' => 'Kurangi Stok (Keluar)',
                    ])
                    ->required()
                    ->disabled($isReadOnly),

                TextInput::make('qty')
                    ->label('Kuantitas (Qty)')
                    ->numeric()
                    ->required()
                    ->disabled($isReadOnly),

                TextInput::make('current_stock_at_request')
                    ->label('Stok Sistem Saat Pengajuan')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                TextInput::make('status')
                    ->label('Status')
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),

                Textarea::make('reason')
                    ->label('Alasan Koreksi')
                    ->required()
                    ->disabled($isReadOnly)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}

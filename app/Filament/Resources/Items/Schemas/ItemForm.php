<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Barang / SKU')
                    ->default(function () {
                        $lastItem = Item::latest('id')->first();
                        if (!$lastItem) {
                            return 'BRG-0001';
                        }
                        $lastNumber = (int) substr($lastItem->code, 4);
                        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                        return 'BRG-' . $nextNumber;
                    })
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Barang')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->default(0),
                Select::make('unit')
                    ->label('Satuan')
                    ->options([
                        'Pcs' => 'Pcs (Potong)',
                        'Box' => 'Box (Kotak)',
                        'Rim' => 'Rim (Kertas)',
                        'Pack' => 'Pack',
                        'Botol' => 'Botol',
                    ])
                    ->required(),
            ]);
    }
}

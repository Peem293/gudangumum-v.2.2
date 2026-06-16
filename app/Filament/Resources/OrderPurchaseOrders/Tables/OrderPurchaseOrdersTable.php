<?php

namespace App\Filament\Resources\OrderPurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderPurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')->label('Nomor PO')->searchable(),
                TextColumn::make('supplier.name')->label('Supplier'),
                TextColumn::make('grand_total')->label('Grand Total')->money('IDR'),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'approved',
                        'warning' => 'order',
                    ])

            ])
            ->actions([
                ViewAction::make(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

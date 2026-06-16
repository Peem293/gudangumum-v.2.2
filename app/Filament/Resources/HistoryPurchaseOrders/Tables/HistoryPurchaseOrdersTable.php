<?php

namespace App\Filament\Resources\HistoryPurchaseOrders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class HistoryPurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('po_date')
                    ->label('Tanggal PO')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier / Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn () => 'Received / Complete'),
                TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->toolbarActions([]);
    }
}

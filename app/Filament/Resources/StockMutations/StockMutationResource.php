<?php

namespace App\Filament\Resources\StockMutations;

use App\Filament\Resources\StockMutations\Pages\ListStockMutations;
use App\Models\StockMutation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMutationResource extends Resource
{
    protected static ?string $model = StockMutation::class;

    protected static ?string $slug = 'stock-mutations';
    protected static ?string $navigationLabel = 'Kartu Stok';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        // Read-only, no form creation needed
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('item.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('item.code')
                    ->label('Kode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in'  => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in'  => '↑ Masuk',
                        'out' => '↓ Keluar',
                        default => $state,
                    }),

                TextColumn::make('beginning_stock')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->alignCenter()
                    ->color('gray'),

                TextColumn::make('qty')
                    ->label('Qty Mutasi')
                    ->numeric()
                    ->alignCenter()
                    ->color(fn ($record) => $record?->type === 'in' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state, $record) => ($record?->type === 'in' ? '+' : '-') . number_format($state)),

                TextColumn::make('ending_stock')
                    ->label('Saldo Akhir')
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'danger'),

                TextColumn::make('item.unit')
                    ->label('Satuan')
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Harga Saat Transaksi')
                    ->money('IDR', locale: 'id')
                    ->toggleable(),

                TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Mutasi')
                    ->options([
                        'in'  => 'Masuk (In)',
                        'out' => 'Keluar (Out)',
                    ]),
                SelectFilter::make('item_id')
                    ->label('Barang')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([])        // Read-only: no row actions
            ->recordActions([])  // Read-only: no row actions
            ->bulkActions([])    // Read-only: no bulk actions
            ->toolbarActions([]); // Read-only: no toolbar actions
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMutations::route('/'),
        ];
    }

    // Tidak bisa create, update, delete
    public static function canCreate(): bool { return false; }
}

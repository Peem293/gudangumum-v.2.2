<?php

namespace App\Filament\Resources\Pajaks;

use App\Filament\Resources\Pajaks\Pages\ManagePajaks;
use App\Models\Pajak;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle as FormToggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PajakResource extends Resource
{
    protected static ?string $model = Pajak::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Pajak / PPN';
    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'Pajak';
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan Sistem';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('ppn')
                    ->required()
                    ->numeric(),
                FormToggle::make('status')
                    ->label('Status Aktif')
                    ->helperText('Aktifkan agar pajak ini otomatis dipilih oleh sistem pada pembuatan Purchase Order.')
                    ->default(true),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Pajak')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('ppn')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ppn')
                    ->label('Besaran Pajak')
                    ->numeric(2) // Menampilkan 2 digit desimal seperti struktur database (11.00)
                    ->suffix('%')
                    ->sortable(),
                // Mengubah tampilan status menjadi ToggleColumn agar bisa diklik langsung dari baris tabel
                ToggleColumn::make('status')
                    ->label('Status Aktif')
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {
                        // LOGIKA BONUS: Jika user mengaktifkan pajak ini (true),
                        // otomatis nonaktifkan pajak lainnya agar status aktif HANYA ADA SATU.
                        if ($state) {
                            Pajak::where('id', '!=', $record->id)->update(['status' => false]);
                        }
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePajaks::route('/'),
        ];
    }
}

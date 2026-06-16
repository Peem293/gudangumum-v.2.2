<?php

namespace App\Filament\Resources\AdjustmentRequests;

use App\Filament\Resources\AdjustmentRequests\Pages\CreateAdjustmentRequest;
use App\Filament\Resources\AdjustmentRequests\Pages\EditAdjustmentRequest;
use App\Filament\Resources\AdjustmentRequests\Pages\ListAdjustmentRequests;
use App\Filament\Resources\AdjustmentRequests\Pages\ViewAdjustmentRequest;
use App\Filament\Resources\AdjustmentRequests\Schemas\AdjustmentRequestForm;
use App\Filament\Resources\AdjustmentRequests\Tables\AdjustmentRequestsTable;
use App\Models\AdjustmentRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdjustmentRequestResource extends Resource
{
    protected static ?string $model = AdjustmentRequest::class;

    protected static ?string $slug = 'adjustment-requests';
    protected static ?string $navigationLabel = 'Stock Adjustments';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $recordTitleAttribute = 'adjustment_number';

    public static function form(Schema $schema): Schema
    {
        return AdjustmentRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdjustmentRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdjustmentRequests::route('/'),
            'create' => CreateAdjustmentRequest::route('/create'),
            'view' => ViewAdjustmentRequest::route('/{record}'),
            'edit' => EditAdjustmentRequest::route('/{record}/edit'),
        ];
    }
}

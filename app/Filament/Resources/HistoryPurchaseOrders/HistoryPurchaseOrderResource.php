<?php

namespace App\Filament\Resources\HistoryPurchaseOrders;

use App\Filament\Resources\HistoryPurchaseOrders\Pages\ListHistoryPurchaseOrders;
use App\Filament\Resources\HistoryPurchaseOrders\Pages\ViewHistoryPurchaseOrder;
use App\Filament\Resources\HistoryPurchaseOrders\Schemas\HistoryPurchaseOrderForm;
use App\Filament\Resources\HistoryPurchaseOrders\Schemas\HistoryPurchaseOrderInfolist;
use App\Filament\Resources\HistoryPurchaseOrders\Tables\HistoryPurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class HistoryPurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $slug = 'purchase-orders-complete';

    protected static ?string $navigationLabel = 'Purchase Complete';
    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $recordTitleAttribute = 'po_number';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('status', 'received');
    }

    public static function form(Schema $schema): Schema
    {
        return HistoryPurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HistoryPurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistoryPurchaseOrdersTable::configure($table);
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
            'index' => ListHistoryPurchaseOrders::route('/'),
            'view' => ViewHistoryPurchaseOrder::route('/{record}'),
        ];
    }

    /**
     * Finance Manager dan Purchasing Manager bisa lihat Purchase Complete.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        return $user->hasRole(['administrator', 'direktur', 'admin_gudang'])
            || $user->isPurchasingManager()
            || $user->isFinanceManager();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

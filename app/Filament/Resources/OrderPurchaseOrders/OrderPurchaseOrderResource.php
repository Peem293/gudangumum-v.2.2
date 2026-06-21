<?php

namespace App\Filament\Resources\OrderPurchaseOrders;

// use App\Filament\Resources\OrderPurchaseOrders\Pages\CreateOrderPurchaseOrder;
// use App\Filament\Resources\OrderPurchaseOrders\Pages\EditOrderPurchaseOrder;
use App\Filament\Resources\OrderPurchaseOrders\Pages\ListOrderPurchaseOrders;
use App\Filament\Resources\OrderPurchaseOrders\Pages\ViewOrderPurchaseOrder;
use App\Filament\Resources\OrderPurchaseOrders\Schemas\OrderPurchaseOrderForm;
use App\Filament\Resources\OrderPurchaseOrders\Schemas\OrderPurchaseOrderInfolist;
use App\Filament\Resources\OrderPurchaseOrders\Tables\OrderPurchaseOrdersTable;
// use App\Models\OrderPurchaseOrder;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
// use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class OrderPurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $slug = 'purchase-orders-process';
    protected static ?string $navigationLabel = 'Purchase Process';
    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $recordTitleAttribute = 'PurchaseOrder';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Hanya memunculkan status approved dan order
        return parent::getEloquentQuery()->whereIn('status', ['approved', 'order']);
    }

    public static function form(Schema $schema): Schema
    {
        return OrderPurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderPurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderPurchaseOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Purchasing Manager, Finance Manager, dan admin roles bisa melihat Purchase Process.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        return $user->hasRole(['administrator', 'direktur', 'manager_jangum', 'manager_keuangan', 'admin_gudang'])
            || $user->isPurchasingManager()
            || $user->isFinanceManager();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderPurchaseOrders::route('/'),
            // 'create' => CreateOrderPurchaseOrder::route('/create'),
            'view' => ViewOrderPurchaseOrder::route('/{record}'),
            // 'edit' => EditOrderPurchaseOrder::route('/{record}/edit'),
        ];
    }
}

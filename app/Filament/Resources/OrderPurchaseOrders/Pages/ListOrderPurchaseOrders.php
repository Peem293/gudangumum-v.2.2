<?php

namespace App\Filament\Resources\OrderPurchaseOrders\Pages;

use App\Filament\Resources\OrderPurchaseOrders\OrderPurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderPurchaseOrders extends ListRecords
{
    protected static string $resource = OrderPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

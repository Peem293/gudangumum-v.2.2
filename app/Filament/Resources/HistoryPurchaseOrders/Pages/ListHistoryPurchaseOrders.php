<?php

namespace App\Filament\Resources\HistoryPurchaseOrders\Pages;

use App\Filament\Resources\HistoryPurchaseOrders\HistoryPurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHistoryPurchaseOrders extends ListRecords
{
    protected static string $resource = HistoryPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

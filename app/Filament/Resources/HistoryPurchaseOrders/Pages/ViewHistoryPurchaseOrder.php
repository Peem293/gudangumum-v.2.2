<?php

namespace App\Filament\Resources\HistoryPurchaseOrders\Pages;

use App\Filament\Resources\HistoryPurchaseOrders\HistoryPurchaseOrderResource;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewHistoryPurchaseOrder extends ViewRecord
{
    protected static string $resource = HistoryPurchaseOrderResource::class;

    public function form(Schema $schema): Schema
    {
        return PurchaseOrderResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak PO (PDF)')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('purchase-orders.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}

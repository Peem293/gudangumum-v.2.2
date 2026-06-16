<?php

namespace App\Filament\Resources\OrderPurchaseOrders\Pages;

use \Filament\Notifications\Notification;
use App\Filament\Resources\OrderPurchaseOrders\OrderPurchaseOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditOrderPurchaseOrder extends EditRecord
{
    protected static string $resource = OrderPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
            ->label('Approve PO')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn () => $this->record->isDraft()) // Hanya muncul jika masih draft
            ->action(function () {
                $this->record->update(['status' => 'approved']);
                Notification::make()->title('PO Approved!')->send();
                $this->redirect($this->getResource()::getUrl('index'));
            }),
            DeleteAction::make(),
        ];
    }
}

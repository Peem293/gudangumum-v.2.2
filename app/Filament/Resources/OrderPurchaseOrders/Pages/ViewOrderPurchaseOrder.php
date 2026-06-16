<?php

namespace App\Filament\Resources\OrderPurchaseOrders\Pages;

// 1. IMPORT KEDUA RESOURCE-NYA UNTUK MEMBAGI TUGAS
use App\Filament\Resources\OrderPurchaseOrders\OrderPurchaseOrderResource; // Resource menu proses saat ini
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource; // Resource asal skema form utama
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class ViewOrderPurchaseOrder extends ViewRecord
{
    // 2. PERBAIKAN UTAMA: Kembalikan ke Resource menu proses Anda agar tidak 404!
    protected static string $resource = OrderPurchaseOrderResource::class;

    /**
     * Mengambil skema form dari PurchaseOrderResource utama agar tampil otomatis secara Read-Only
     */
    public function form(Schema $schema): Schema
    {
        return PurchaseOrderResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            // TOMBOL WORKFLOW 1: UBAH STATUS JADI ORDER
            Action::make('orderSupplier')
                ->label('Order ke Supplier')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'approved')
                ->action(function () {
                    $this->record->update(['status' => 'order']);

                    $this->js("window.open('" . route('purchase-orders.print', $this->record) . "', '_blank');");

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // TOMBOL WORKFLOW 2: BARANG DATANG (RECEIVED) + MUTASI STOK
            Action::make('receiveBarang')
                ->label('Barang Datang (Received)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'order')
                ->action(function () {

                    DB::transaction(function () {
                        $this->record->update(['status' => 'received']);

                        foreach ($this->record->details as $detail) {
                            if ($detail->item && method_exists($detail->item, 'updateStockWithMutation')) {
                                $detail->item->updateStockWithMutation(
                                    type: 'in',
                                    qty: $detail->qty,
                                    reference: $this->record->po_number,
                                    price: $detail->price_at_purchase
                                );
                            }
                        }
                    });

                    Notification::make()
                        ->title('Barang Berhasil Diterima! Stok gudang telah bertambah otomatis.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // TOMBOL CETAK STANDALONE
            Action::make('print')
                ->label('Cetak PO')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('purchase-orders.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['approved', 'order'])),
        ];
    }
}

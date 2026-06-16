<?php

namespace App\Filament\Resources\AdjustmentRequests\Pages;

use App\Filament\Resources\AdjustmentRequests\AdjustmentRequestResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewAdjustmentRequest extends ViewRecord
{
    protected static string $resource = AdjustmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Manager Keuangan Approval Action
            Action::make('approveManager')
                ->label('Setujui Pengajuan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    return $this->record->status === 'pending' &&
                        Auth::user()->hasRole(['manager_keuangan', 'administrator']);
                })
                ->action(function () {
                    $this->record->update([
                        'status' => 'approved_by_manager',
                        'approved_by_id' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan Disetujui Manager Keuangan!')->success()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // Manager Keuangan Reject Action
            Action::make('rejectManager')
                ->label('Tolak Pengajuan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(function () {
                    return $this->record->status === 'pending' &&
                        Auth::user()->hasRole(['manager_keuangan', 'administrator']);
                })
                ->action(function () {
                    $this->record->update([
                        'status' => 'rejected',
                        'approved_by_id' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan Ditolak!')->danger()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // Administrator Execution Action
            Action::make('executeAdmin')
                ->label('Eksekusi Penyesuaian')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    return $this->record->status === 'approved_by_manager' &&
                        Auth::user()->hasRole(['administrator']);
                })
                ->action(function () {
                    try {
                        DB::transaction(function () {
                            $adj = $this->record;
                            $item = \App\Models\Item::where('id', $adj->item_id)->lockForUpdate()->first();

                            if (!$item) {
                                throw new \Exception("Barang tidak ditemukan.");
                            }

                            if ($adj->type === 'out' && $item->stock < $adj->qty) {
                                throw new \Exception("Stok barang '{$item->name}' tidak mencukupi untuk penyesuaian keluar. Sisa stok: {$item->stock}.");
                            }

                            // Update stock with mutation history
                            $item->updateStockWithMutation(
                                type: $adj->type,
                                qty: $adj->qty,
                                reference: $adj->adjustment_number,
                                price: $item->price
                            );

                            // Mark as executed
                            $adj->update([
                                'status' => 'executed_by_admin',
                                'executed_by_id' => Auth::id(),
                            ]);
                        });

                        Notification::make()
                            ->title('Penyesuaian Stok Berhasil Dieksekusi!')
                            ->body('Stok barang telah disesuaikan dan dicatat di mutasi stok.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Mengeksekusi Penyesuaian')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Requests\Pages;

use App\Filament\Resources\Requests\RequestResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Action to Approve Request
            Action::make('approve')
                ->label('Approve Permintaan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    return $this->record->status === 'pending' &&
                        Auth::user()->hasRole(['manager', 'administrator']) &&
                        (Auth::user()->hasRole('administrator') || Auth::user()->department_id === $this->record->department_id);
                })
                ->action(function () {
                    $approver = Auth::user();
                    $record = $this->record;

                    if (!$approver->private_key) {
                        Notification::make()->title('Gagal Approve')->body('Aktifkan Kunci TTD terlebih dahulu.')->danger()->send();
                        return;
                    }

                    $cleanName = trim($approver->name);
                    
                    // Konsistensi format desimal wajib agar signature valid
                    $formattedTotal = number_format((float)$record->total_amount, 2, '.', '');
                    
                    $dataToSign = "DocID:{$record->id}" .
                                  "|ReqNo:{$record->request_number}" . 
                                  "|Total:{$formattedTotal}" . 
                                  "|Status:approved" .
                                  "|Approver:{$cleanName}" .
                                  "|ApproverID:{$approver->id}";
                                  
                    $privateKeyDecrypted = \Illuminate\Support\Facades\Crypt::decryptString($approver->private_key);
                    $signature = '';
                    openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256);
                    
                    $record->update([
                        'status' => 'approved',
                        'approved_by_id' => $approver->id,
                        'signature' => base64_encode($signature),
                    ]);

                    Notification::make()->title('Permintaan Disetujui!')->success()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // Action to Reject Request
            Action::make('reject')
                ->label('Reject Permintaan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(function () {
                    return $this->record->status === 'pending' &&
                        Auth::user()->hasRole(['manager', 'administrator']) &&
                        (Auth::user()->hasRole('administrator') || Auth::user()->department_id === $this->record->department_id);
                })
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);
                    Notification::make()->title('Permintaan Ditolak!')->danger()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // Action to Complete & Dispatch
            Action::make('complete')
                ->label('Selesaikan & Kirim')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    return $this->record->status === 'approved' &&
                        Auth::user()->hasRole(['admin_gudang', 'administrator']);
                })
                ->action(function () {
                    try {
                        DB::transaction(function () {
                            $request = $this->record;
                            $totalAmount = 0;
                            $request->load('details.item');

                            foreach ($request->details as $detail) {
                                $item = \App\Models\Item::where('id', $detail->item_id)->lockForUpdate()->first();
                                if (!$item) throw new \Exception("Barang dengan ID {$detail->item_id} tidak ditemukan.");
                                if ($item->stock < $detail->qty_requested) throw new \Exception("Stok barang '{$item->name}' tidak mencukupi.");

                                $item->updateStockWithMutation('out', $detail->qty_requested, $request->request_number, $item->price);
                                $detail->update([
                                    'price_at_transaction' => $item->price,
                                    'subtotal' => $item->price * $detail->qty_requested,
                                ]);
                                $totalAmount += ($item->price * $detail->qty_requested);
                            }

                            $request->update(['status' => 'completed', 'total_amount' => $totalAmount]);
                        });

                        Notification::make()->title('Permintaan Berhasil Diselesaikan!')->success()->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()->title('Gagal Menyelesaikan Permintaan')->body($e->getMessage())->danger()->persistent()->send();
                    }
                }),

            EditAction::make()->visible(fn () => $this->record->status === 'pending'),

            Action::make('print')
                ->label('Cetak Permintaan (PDF)')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('requests.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['approved', 'completed'])),
        ];
    }
}
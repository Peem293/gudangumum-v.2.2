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

                    // 1. VALIDASI: Pastikan Atasan sudah melakukan Aktivasi TTD di Step 1
                    if (!$approver->private_key) {
                        Notification::make()
                            ->title('Gagal Approve')
                            ->body('Anda belum mengaktifkan Kunci TTD Digital. Silakan aktivasi terlebih dahulu di menu Users.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // 2. KUMPULKAN DATA UNIK: Menyusun string data krusial dokumen yang tidak boleh dimanipulasi
                    // Gunakan properti bawaan kode kamu: $this->record
                    // Silakan sesuaikan kolom detail (seperti code/nama/qty) jika nama kolom di databasemu berbeda
                    $dataToSign = "DocID:" . $this->record->id . 
                                "|Status:approved" .
                                "|Approver:" . $approver->name .
                                "|ApproverID:" . $approver->id;

                    // 3. DEKRIP PRIVATE KEY ATASAN
                    $privateKeyDecrypted = \Illuminate\Support\Facades\Crypt::decryptString($approver->private_key);

                    // 4. PROSES SIGNING: Membuat Digital Signature unik dengan OpenSSL
                    $signature = '';
                    openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256);
                    
                    // Ubah hasil biner signature menjadi teks string base64 agar aman disimpan di DB
                    $encodedSignature = base64_encode($signature);

                    // 5. UPDATE DATABASE (Tetap menggunakan fungsi bawaanmu, hanya ditambah kolom signature)
                    $this->record->update([
                        'status' => 'approved',
                        'approved_by_id' => Auth::id(),
                        'signature' => $encodedSignature, // Kolom baru yang ditambahkan dari hasil migrasi
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

            // Action to Complete & Dispatch (Admin Gudang)
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

                            // Reload details to ensure fresh state
                            $request->load('details.item');

                            foreach ($request->details as $detail) {
                                // Lock the Item record pessimisticly
                                $item = \App\Models\Item::where('id', $detail->item_id)->lockForUpdate()->first();

                                if (!$item) {
                                    throw new \Exception("Barang dengan ID {$detail->item_id} tidak ditemukan.");
                                }

                                if ($item->stock < $detail->qty_requested) {
                                    throw new \Exception("Stok barang '{$item->name}' tidak mencukupi. Sisa stok: {$item->stock}.");
                                }

                                // Update stock level and mutation card
                                $item->updateStockWithMutation(
                                    type: 'out',
                                    qty: $detail->qty_requested,
                                    reference: $request->request_number,
                                    price: $item->price
                                );

                                // Snapshot actual transaction price and calculate subtotal
                                $detail->update([
                                    'price_at_transaction' => $item->price,
                                    'subtotal' => $item->price * $detail->qty_requested,
                                ]);

                                $totalAmount += ($item->price * $detail->qty_requested);
                            }

                            // Finalize Request status and header amount
                            $request->update([
                                'status' => 'completed',
                                'total_amount' => $totalAmount,
                            ]);
                        });

                        Notification::make()
                            ->title('Permintaan Berhasil Diselesaikan!')
                            ->body('Stok gudang telah dikurangi dan mutasi stok tercatat.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Menyelesaikan Permintaan')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),

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

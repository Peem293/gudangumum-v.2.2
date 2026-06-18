<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TOMBOL APPROVE KHUSUS ADMIN & MANAGER PENUNJANG UMUM + VALIDASI TTD DIGITAL
            Action::make('approve')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    // 1. Tombol hanya muncul jika status PO di database saat ini adalah 'draft'
                    if ($this->record->status !== 'draft') {
                        return false;
                    }

                    // 2. Ambil data user yang sedang login
                    $user = Auth::user();
                    if (!$user) {
                        return false;
                    }

                    /**
                     * KONDISI HAK AKSES (Spatie / Filament Shield):
                     * - Punya role 'super_admin' ATAU 'administrator'
                     * - ATAU punya role 'manager' DAN berada di departemen 'Penunjang Umum'
                     */
                    $isAdmin = $user->hasRole(['super_admin', 'administrator']);

                    // Pastikan Model User Anda memiliki relasi 'department' ke tabel departments
                    $isManagerPenunjangUmum = $user->hasRole('manager') &&
                        ($user->department?->name === 'Penunjang Umum');

                    // Tombol HANYA MUNCUL jika salah satu kondisi di atas terpenuhi
                    return $isAdmin || $isManagerPenunjangUmum;
                })
                ->action(function () {
                    $approver = Auth::user();

                    // ==========================================
                    // TAHAP 1: VALIDASI UTAMA KUNCI TTD DIGITAL
                    // ==========================================
                    if (!$approver || !$approver->private_key) {
                        Notification::make()
                            ->title('Gagal Menyetujui PO')
                            ->body('Anda belum mengaktifkan Kunci TTD Digital. Silakan aktivasi terlebih dahulu di menu Users / Profil Anda.')
                            ->danger()
                            ->send();
                            
                        return; // Menghentikan eksekusi action
                    }

                    try {
                        // ==========================================
                        // TAHAP 2: PROSES LOGIKA OPENSSL SIGNATURE
                        // ==========================================
                        // 1. Susun string data penanda tangan untuk Approval
                        $dataToSign = "DocID:" . $this->record->id . 
                                    "|Status:approved" .
                                    "|Approver:" . $approver->name .
                                    "|ApproverID:" . $approver->id;

                        // 2. Dekrip Private Key milik Manager/Admin yang log-in
                        $privateKeyDecrypted = Crypt::decryptString($approver->private_key);

                        // 3. Generate Signature
                        $signature = '';
                        if (openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256)) {
                            
                            // ==========================================
                            // TAHAP 3: UPDATE DATABASE ATOMIC (TERMASUK DATA TTD)
                            // ==========================================
                            // Menggunakan DB::table untuk memastikan query masuk secara paksa dan bypass row-locking
                            DB::table('purchase_orders')
                                ->where('id', $this->record->id)
                                ->update([
                                    'status' => 'approved',
                                    'approved_by_id' => $approver->id,
                                    'approved_signature' => base64_encode($signature),
                                    'updated_at' => now(),
                                ]);

                            Notification::make()
                                ->title('Purchase Order Berhasil Di-approve!')
                                ->success()
                                ->send();

                            // Redirect kembali ke halaman index list PO Draft
                            $this->redirect($this->getResource()::getUrl('index'));

                        } else {
                            throw new \Exception("Logika Enkripsi OpenSSL mendeteksi key tidak valid.");
                        }

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Gagal melakukan Approval TTD Digital PO: " . $e->getMessage());
                        
                        Notification::make()
                            ->title('Error Penandatanganan')
                            ->body('Gagal membuat tanda tangan digital. Pastikan format key Anda valid.')
                            ->danger()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }

    // Redirect bawaan jika menekan tombol 'Save' biasa di bawah form
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
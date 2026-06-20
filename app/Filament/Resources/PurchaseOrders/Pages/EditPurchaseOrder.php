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
use Illuminate\Support\Facades\Log;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    if ($this->record->status !== 'draft') {
                        return false;
                    }
                    $user = Auth::user();
                    if (!$user) return false;

                    $isAdmin = $user->hasRole(['super_admin', 'administrator']);
                    $isManagerPenunjangUmum = $user->hasRole('manager') &&
                        ($user->department?->name === 'Penunjang Umum');

                    return $isAdmin || $isManagerPenunjangUmum;
                })
                ->action(function () {
                    $approver = Auth::user();

                    if (!$approver || !$approver->private_key) {
                        Notification::make()
                            ->title('Gagal Menyetujui PO')
                            ->body('Anda belum mengaktifkan Kunci TTD Digital.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        // 1. Ambil data dengan benar (gunakan trim untuk nama untuk konsistensi)
                        $cleanName = trim($approver->name);
                        
                        // 2. MASUKKAN SEMUA DATA KRUSIAL KE SINI (Harus sama urutannya dengan Controller!)
                        // Pastikan variabel $this->record->po_number dan $this->record->grand_total ada
                        $formattedTotal = number_format((float)$this->record->grand_total, 2, '.', '');

                        $dataToSign = "DocID:{$this->record->id}" .
                                    "|PoNo:{$this->record->po_number}" . 
                                    "|Total:{$formattedTotal}" . // Pakai variable ini
                                    "|Status:approved" .
                                    "|Approver:{$cleanName}" .
                                    "|ApproverID:{$approver->id}" .
                                    "|doc:po";

                        $privateKeyDecrypted = Crypt::decryptString($approver->private_key);
                        $configArgs = get_openssl_config_args();
                        
                        $privateKeyResource = !empty($configArgs) 
                            ? openssl_pkey_get_private($privateKeyDecrypted, $configArgs['config']) 
                            : openssl_pkey_get_private($privateKeyDecrypted);

                        if (!$privateKeyResource) {
                            throw new \Exception("Gagal memuat Private Key.");
                        }

                        $signature = '';
                        if (openssl_sign($dataToSign, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
                            
                            DB::table('purchase_orders')
                                ->where('id', $this->record->id)
                                ->update([
                                    'status' => 'approved',
                                    'approved_by_id' => $approver->id,
                                    'approved_signature' => base64_encode($signature),
                                    'updated_at' => now(),
                                ]);

                            Notification::make()->title('Purchase Order Berhasil Di-approve!')->success()->send();
                            $this->redirect($this->getResource()::getUrl('index'));

                        } else {
                            throw new \Exception("Logika OpenSSL gagal menghasilkan tanda tangan.");
                        }

                    } catch (\Exception $e) {
                        Log::error("Approval TTD Gagal: " . $e->getMessage());
                        Notification::make()
                            ->title('Error Penandatanganan')
                            ->body('Gagal membuat tanda tangan digital.')
                            ->danger()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
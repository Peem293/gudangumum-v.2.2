<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /**
     * PROSES UTAMA: VALIDASI & GENERATE SIGNATURE SEBELUM DATA MASUK DATABASE
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $creator = Auth::user();

        if (!$creator || !$creator->private_key) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal Membuat Purchase Order')
                ->body('Anda belum mengaktifkan Kunci TTD Digital.')
                ->danger()
                ->send();
            $this->halt();
        }

        try {
            // GANTI: Jangan gunakan time(), gunakan data yang tersimpan di database
            $identifier = $data['po_number']; 
            
            $dataToSign = "DocType:PO" .
                        "|Identifier:" . $identifier . 
                        "|Status:pending" .
                        "|Creator:" . $creator->name .
                        "|CreatorID:" . $creator->id;

            $privateKeyDecrypted = Crypt::decryptString($creator->private_key);

            $signature = '';
            if (openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256)) {
                $data['created_signature'] = base64_encode($signature);
                $data['user_id'] = $creator->id;
            } else {
                \Illuminate\Support\Facades\Log::error("OpenSSL gagal membuat signature: " . openssl_error_string());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal TTD: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Kosongkan afterCreate karena proses TTD sudah didelegasikan sepenuhnya ke tahapan sebelum insert
     */
    protected function afterCreate(): void
    {
        // Bagian clearTempReservation tetap dikosongkan agar menghindari BadMethodCallException
    }
}
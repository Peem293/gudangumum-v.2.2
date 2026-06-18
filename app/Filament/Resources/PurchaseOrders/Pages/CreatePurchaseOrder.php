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

        // 1. Validasi Keberadaan User dan Private Key
        if (!$creator || !$creator->private_key) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal Membuat Purchase Order')
                ->body('Anda belum mengaktifkan Kunci TTD Digital. Silakan aktivasi terlebih dahulu di menu Users / Profil Anda.')
                ->danger()
                ->send();
                
            $this->halt(); // Menghentikan submit jika key kosong
        }

        try {
            // 2. Susun string unik data penanda tangan
            // Karena ID belum ada (belum insert), gunakan data nomor PO atau timestamp sebagai identitas unik sementara
            $uniqueIdentifier = $data['po_number'] ?? ('PO-' . time());
            
            $dataToSign = "DocType:PO" .
                        "|Identifier:" . $uniqueIdentifier . 
                        "|Status:pending" .
                        "|Creator:" . $creator->name .
                        "|CreatorID:" . $creator->id;

            // 3. Dekrip Private Key milik user
            $privateKeyDecrypted = Crypt::decryptString($creator->private_key);

            // 4. Proses pembuatan signature menggunakan OpenSSL
            $signature = '';
            if (openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256)) {
                
                // 5. MASUKKAN LANGSUNG KE ARRAY DATA FILAMENT
                // Data ini akan otomatis ikut disimpan saat Filament melakukan query INSERT INTO
                $data['created_signature'] = base64_encode($signature);
                
                // Pastikan user_id pencatat juga terisi menggunakan user yang sedang login
                $data['user_id'] = $creator->id;
                
            } else {
                \Illuminate\Support\Facades\Log::error("OpenSSL gagal membuat signature di PO. Error: " . openssl_error_string());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal memproses TTD Digital PO di mutateFormData: " . $e->getMessage());
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
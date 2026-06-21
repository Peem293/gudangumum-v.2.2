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
    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
        // $creator = Auth::user();

        // if (!$creator || !$creator->private_key) {
        //     // ... (kode notifikasi tetap)
        //     return $data; 
        // }

        // try {
        //     $poNumber = $data['po_number'] ?? 'N/A';
        //     $grandTotal = (float)($data['grand_total'] ?? 0);
        //     $formattedTotal = number_format($grandTotal, 2, '.', '');

        //     $dataToSign = "DocID:{$this->record->id}" .
        //                 "|PoNo:{$poNumber}" . 
        //                 "|Total:{$formattedTotal}" . // Pakai variable ini
        //                 "|Status:draft" .
        //                 "|Creator:{$creator->name}" .
        //                 "|CreatorID:{$creator->id}" .
        //                 "|doc:po";
            
        //     // $dataToSign = "DocType:PO|PoNo:{$poNumber}|Total:{$formattedTotal}|Status:draft|Creator:{$creator->name}|CreatorID:{$creator->id}";

        //     // DECRYPT KEY
        //     $privateKeyDecrypted = Crypt::decryptString($creator->private_key);

        //     $signature = '';
        //     if (openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256)) {
        //         $data['created_signature'] = base64_encode($signature);
        //         // Pastikan user_id juga diisi
        //         $data['user_id'] = $creator->id;
        //     } else {
        //         // LOG ERROR JIKA OPENSSL GAGAL
        //         \Illuminate\Support\Facades\Log::error("Tanda Tangan Gagal: " . openssl_error_string());
        //     }
        // } catch (\Exception $e) {
        //     \Illuminate\Support\Facades\Log::error("Exception Tanda Tangan: " . $e->getMessage());
        // }

        // return $data;
    // }

    /**
     * Kosongkan afterCreate karena proses TTD sudah didelegasikan sepenuhnya ke tahapan sebelum insert
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        $creator = Auth::user();

        if ($creator && $creator->private_key) {
            $formattedTotal = number_format((float)$record->grand_total, 2, '.', '');

            // Sekarang $record->id sudah tersedia!
            $dataToSign = "DocID:{$record->id}" .
                        "|PoNo:{$record->po_number}" . 
                        "|Total:{$formattedTotal}" . 
                        "|Status:draft" .
                        "|Creator:{$creator->name}" .
                        "|CreatorID:{$creator->id}" .
                        "|Type:creator" . // <--- Tambahkan ini
                        "|doc:po";

            try {
                $privateKeyDecrypted = Crypt::decryptString($creator->private_key);
                $signature = '';
                if (openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256)) {
                    // Update record langsung setelah dibuat
                    $record->update([
                        'created_signature' => base64_encode($signature),
                        'user_id' => $creator->id
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal TTD di afterCreate: " . $e->getMessage());
            }
        }
    }
}
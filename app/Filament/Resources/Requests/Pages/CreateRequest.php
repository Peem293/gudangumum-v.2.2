<?php

namespace App\Filament\Resources\Requests\Pages;

use App\Filament\Resources\Requests\RequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $creator = \Illuminate\Support\Facades\Auth::user();

        // ==========================================
        // VALIDASI TTD DIGITAL (TAMBAHAN)
        // ==========================================
        // Memastikan staf pembuat sudah klik "Aktivasi TTD" di menu user
        if (!$creator || !$creator->private_key) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal Membuat Permintaan')
                ->body('Anda belum mengaktifkan Kunci TTD Digital. Silakan aktivasi terlebih dahulu di menu Users / Profil Anda.')
                ->danger()
                ->send();
                
            $this->halt(); // Menghentikan proses submit Filament jika belum aktivasi TTD
        }

        // ==========================================
        // LOGIKA PENOMORAN OTOMATIS (BAWAAN KAMU - TETAP UTUH)
        // ==========================================
        $datePrefix = date('ymd');
        
        $lastReq = \App\Models\Request::where('request_number', 'like', "REQ-{$datePrefix}%")
            ->lockForUpdate()
            ->latest('id')
            ->first();
            
        if (!$lastReq) {
            $nextNumber = "REQ-{$datePrefix}001";
        } else {
            $lastNumber = (int) substr($lastReq->request_number, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            $nextNumber = "REQ-{$datePrefix}" . $nextNumber;
        }
        
        $data['request_number'] = $nextNumber;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $creator = \Illuminate\Support\Facades\Auth::user();
        $record = $this->record;

        if ($creator && $creator->private_key) {
            // Gunakan number_format agar selalu 2 desimal
            $formattedTotal = number_format((float)$record->total_amount, 2, '.', '');
            
            $dataToSign = "DocID:{$record->id}" .
                        "|ReqNo:{$record->request_number}" . 
                        "|Total:{$formattedTotal}" . 
                        "|Status:pending" .
                        "|Creator:{$creator->name}" .
                        "|CreatorID:{$creator->id}" .
                        "|Type:creator";

            $privateKeyDecrypted = \Illuminate\Support\Facades\Crypt::decryptString($creator->private_key);
            $signature = '';
            openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256);
            
            $record->creator_signature = base64_encode($signature);
            $record->save();
        }
    }
}

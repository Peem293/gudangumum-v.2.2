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
        $year = date('Y');
        
        $lastReq = \App\Models\Request::where('request_number', 'like', "REQ-{$year}-%")
            ->lockForUpdate()
            ->latest('id')
            ->first();
            
        if (!$lastReq) {
            $nextNumber = "REQ-{$year}-0001";
        } else {
            $lastNumber = (int) substr($lastReq->request_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            $nextNumber = "REQ-{$year}-" . $nextNumber;
        }
        
        $data['request_number'] = $nextNumber;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            foreach ($this->record->details as $detail) {
                \App\Models\Request::clearTempReservation($detail->item_id, $userId);
            }
        }

        $creator = \Illuminate\Support\Facades\Auth::user();
        $record = $this->record;
        // ==========================================
        // LOGIKA GENERATE SIGNATURE PEMINTA (TAMBAHAN)
        // ==========================================
        if ($creator && $creator->private_key) {
            // 1. Susun string unik data penanda tangan (Sama seperti alur sebelumnya)
            $dataToSign = "DocID:" . $record->id . 
                        "|Status:pending" .
                        "|Creator:" . $creator->name .
                        "|CreatorID:" . $creator->id;

            // 2. Dekrip Private Key milik staf yang sedang login
            $privateKeyDecrypted = \Illuminate\Support\Facades\Crypt::decryptString($creator->private_key);

            // 3. Proses penandatanganan dengan OpenSSL
            $signature = '';
            openssl_sign($dataToSign, $signature, $privateKeyDecrypted, OPENSSL_ALGO_SHA256);

            // 4. Simpan hasilnya ke kolom baru 'creator_signature'
            $record->creator_signature = base64_encode($signature);
            $record->save();
        }
    }
}

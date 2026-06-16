<?php

namespace App\Filament\Resources\Requests\Pages;

use App\Filament\Resources\Requests\RequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $year = date('Y');
        
        // Lock request table to ensure next sequential number is completely unique
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
    }
}

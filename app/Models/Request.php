<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'request_number',
    'user_id',
    'department_id',
    'unit_id',
    'status',
    'total_amount',
    'notes',
    'approved_by_id',
    'signature',
    'creator_signature',
])]
class Request extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(RequestDetail::class);
    }

    public static function getAvailableStock(int $itemId, ?int $excludeRequestId = null, ?int $currentUserId = null): int
    {
        $item = Item::find($itemId);
        if (!$item) {
            return 0;
        }

        // 1. Hitung sisa stok fisik dikurangi permintaan pending/approved di database
        $reservedQty = RequestDetail::where('item_id', $itemId)
            ->whereHas('request', function ($query) use ($excludeRequestId) {
                $query->whereIn('status', ['pending', 'approved']);
                if ($excludeRequestId) {
                    $query->where('id', '!=', $excludeRequestId);
                }
            })
            ->sum('qty_requested');

        $stockAfterDb = $item->stock - (int)$reservedQty;

        // 2. Hitung reservasi sementara (cache) dari user lain yang sedang mengetik di form
        $cacheKey = "item_{$itemId}_temp_reservations";
        $tempReservations = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $tempSum = 0;
        $now = time();
        $updatedTempReservations = [];

        foreach ($tempReservations as $userId => $data) {
            // Lewati jika sudah kedaluwarsa
            if ($data['expires_at'] < $now) {
                continue;
            }
            // Simpan yang masih aktif
            $updatedTempReservations[$userId] = $data;

            // Jumlahkan reservasi dari USER LAIN saja
            if ($currentUserId === null || (int)$userId !== (int)$currentUserId) {
                $tempSum += (int)$data['qty'];
            }
        }

        // Simpan kembali data cache yang bersih dari entri expired
        if (count($updatedTempReservations) !== count($tempReservations)) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $updatedTempReservations, 600);
        }

        return max(0, $stockAfterDb - $tempSum);
    }

    public static function reserveStockTemp(int $itemId, int $userId, int $qty): void
    {
        $cacheKey = "item_{$itemId}_temp_reservations";
        $tempReservations = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $now = time();

        // Bersihkan data lama dan pasang data baru
        $updatedTempReservations = [];
        foreach ($tempReservations as $uId => $data) {
            if ($data['expires_at'] >= $now) {
                $updatedTempReservations[$uId] = $data;
            }
        }

        // Tambah/Update reservasi untuk user saat ini (berlaku selama 5 menit)
        if ($qty > 0) {
            $updatedTempReservations[$userId] = [
                'qty' => $qty,
                'expires_at' => $now + 300, // 5 menit
            ];
        } else {
            unset($updatedTempReservations[$userId]);
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $updatedTempReservations, 600);
    }

    public static function clearTempReservation(int $itemId, int $userId): void
    {
        $cacheKey = "item_{$itemId}_temp_reservations";
        $tempReservations = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        
        if (isset($tempReservations[$userId])) {
            unset($tempReservations[$userId]);
            \Illuminate\Support\Facades\Cache::put($cacheKey, $tempReservations, 600);
        }
    }

    public function recalculateTotalAmount(): void
    {
        $total = $this->details()->sum('subtotal');
        $this->updateQuietly(['total_amount' => $total]);
    }
}

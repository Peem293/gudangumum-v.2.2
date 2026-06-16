<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'request_id',
    'item_id',
    'qty_requested',
    'price_at_transaction',
    'subtotal'
])]
class RequestDetail extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function ($detail) {
            $detail->request?->recalculateTotalAmount();
        });

        static::deleted(function ($detail) {
            $detail->request?->recalculateTotalAmount();
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

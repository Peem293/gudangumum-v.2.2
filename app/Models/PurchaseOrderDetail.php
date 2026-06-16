<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_order_id',
    'item_id',
    'qty',
    'price_at_purchase',
    'subtotal'
])]
class PurchaseOrderDetail extends Model
{
    use HasFactory;

    /**
     * Relasi balik ke nota induk PurchaseOrder
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Relasi ke Master Item Barang
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'po_number',
    'supplier_id',
    'user_id',
    'approved_by_id',
    'pajak_id',
    'ppn_percentage',
    'shipping_cost',
    'po_date',
    'status',
    'grand_total',
    'created_signature',
    'approved_signature',
])]
class PurchaseOrder extends Model
{
    use HasFactory;

    // Method pembantu untuk mengecek status
    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isOrder(): bool { return $this->status === 'order'; }
    public function isReceived(): bool { return $this->status === 'received'; }

    /**
     * Relasi ke Supplier / Vendor Pengirim
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relasi ke User / Admin Pembuat PO
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User / Pihak yang Menyetujui PO
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Relasi ke Master Pajak yang digunakan
     */
    public function pajak(): BelongsTo
    {
        return $this->belongsTo(Pajak::class);
    }

    /**
     * Relasi ke Detail Item Barang di dalam PO (Penting untuk Repeater)
     */
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }
}

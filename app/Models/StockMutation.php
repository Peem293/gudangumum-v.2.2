<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'type',
        'qty',
        'beginning_stock',
        'ending_stock',
        'reference',
        'price',
        'notes',
    ];

    protected $casts = [
        'beginning_stock' => 'integer',
        'ending_stock' => 'integer',
        'qty' => 'integer',
        'price' => 'float',
    ];

    // Relasi ke model Item
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'price', 'stock', 'unit'])]
class Item extends Model
{
    use HasFactory;

    /**
     * Fungsi untuk menambah stok gudang sekaligus mencatat histori mutasi barang
     */
    public function updateStockWithMutation(string $type, int $qty, string $reference, float $price, ?string $notes = null): void
    {
        // 1. Ambil saldo stok SEBELUM transaksi
        $beginningStock = (int) $this->stock;

        // 2. Hitung saldo stok SESUDAH transaksi
        $endingStock = $type === 'in'
            ? ($beginningStock + $qty)
            : ($beginningStock - $qty);

        // 3. Update stok riil di tabel items
        if ($type === 'in') {
            $this->increment('stock', $qty);
        } elseif ($type === 'out') {
            $this->decrement('stock', $qty);
        }

        // 4. Catat ke tabel riwayat mutasi dengan saldo awal dan akhir
        StockMutation::create([
            'item_id'         => $this->id,
            'type'            => $type,
            'qty'             => $qty,
            'beginning_stock' => $beginningStock,
            'ending_stock'    => $endingStock,
            'reference'       => $reference,
            'price'           => $price,
            'notes'           => $notes,
        ]);
    }
}

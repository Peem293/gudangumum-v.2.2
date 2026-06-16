<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade'); // Menghubungkan ke header PO
            $table->foreignId('item_id')->constrained(); // Menghubungkan ke tabel items
            $table->integer('qty');
            $table->decimal('price_at_purchase', 12, 2); // Harga beli riil dikunci di sini
            $table->decimal('subtotal', 12, 2); // qty * price_at_purchase
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_details');
    }
};

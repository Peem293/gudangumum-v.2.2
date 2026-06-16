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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // SKU / Barcode
            $table->string('name');
            $table->decimal('price', 12, 2); // Harga master saat ini
            $table->integer('stock')->default(0); // Default stok awal 0
            $table->string('unit'); // Contoh: 'Pcs', 'Box', 'Rim'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

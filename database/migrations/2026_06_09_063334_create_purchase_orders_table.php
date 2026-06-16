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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // Contoh: PO-2026-0001
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('user_id')->constrained(); // Admin Gudang pembuat
            $table->foreignId('pajak_id')->constrained();
            $table->decimal('shipping_cost', 12, 2)->default(0.00); // Input manual oleh Admin Gudang
            $table->date('po_date');
            $table->enum('status', ['draft', 'ordered', 'received'])->default('draft');
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};

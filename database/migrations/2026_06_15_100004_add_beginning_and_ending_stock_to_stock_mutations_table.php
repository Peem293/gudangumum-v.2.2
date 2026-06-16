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
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->integer('beginning_stock')->default(0)->after('qty'); // Saldo stok SEBELUM mutasi
            $table->integer('ending_stock')->default(0)->after('beginning_stock'); // Saldo stok SESUDAH mutasi
            $table->text('notes')->nullable()->after('price'); // Catatan tambahan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropColumn(['beginning_stock', 'ending_stock', 'notes']);
        });
    }
};

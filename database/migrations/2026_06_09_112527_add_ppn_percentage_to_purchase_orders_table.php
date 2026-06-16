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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Menambahkan field untuk mengunci nilai PPN (misal: 11.00)
            $table->decimal('ppn_percentage', 5, 2)->default(0)->after('pajak_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Menghapus field ppn_percentage jika rollback
            $table->dropColumn('ppn_percentage');
        });
    }
};

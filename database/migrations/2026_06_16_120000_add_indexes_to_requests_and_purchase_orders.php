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
        // Add indexes to requests table for performance
        Schema::table('requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('department_id');
            $table->index('unit_id');
        });

        // Add indexes to purchase_orders table for performance
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['unit_id']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['supplier_id']);
        });
    }
};

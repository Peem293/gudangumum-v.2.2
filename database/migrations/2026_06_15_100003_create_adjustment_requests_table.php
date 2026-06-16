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
        Schema::create('adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique(); // Contoh: ADJ-2026-0001
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin Gudang peminta
            $table->string('type'); // 'in', 'out'
            $table->integer('qty');
            $table->integer('current_stock_at_request'); // Stok sistem saat pengajuan dibuat
            $table->string('status')->default('pending'); // 'pending', 'approved_by_manager', 'rejected', 'executed_by_admin'
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null'); // Manager Keuangan
            $table->foreignId('executed_by_id')->nullable()->constrained('users')->onDelete('set null'); // Administrator
            $table->text('reason'); // Alasan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_requests');
    }
};

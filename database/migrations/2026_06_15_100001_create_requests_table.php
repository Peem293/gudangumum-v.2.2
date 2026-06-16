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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique(); // Contoh: REQ-2026-0001
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Staf peminta
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected', 'completed'
            $table->decimal('total_amount', 12, 2)->default(0.00); // Diisi saat transaksi 'completed'
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};

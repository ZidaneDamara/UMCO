<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method'); // cash|qris
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('cash_received')->nullable();
            $table->unsignedBigInteger('change')->default(0);
            $table->string('reference')->nullable(); // QRIS reference
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

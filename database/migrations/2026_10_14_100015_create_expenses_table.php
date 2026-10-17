<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category'); // e.g. bahan_darurat, operasional, gas_air, lain_lain
            $table->unsignedBigInteger('amount');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['shift_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

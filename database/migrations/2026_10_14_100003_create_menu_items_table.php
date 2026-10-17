<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // drink|food
            $table->unsignedBigInteger('price'); // integer rupiah
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_available']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};

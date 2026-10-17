<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type'); // drink|food
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->string('station')->default('bar'); // bar|kitchen
            $table->string('status')->default('confirmed'); // confirmed|preparing|ready
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

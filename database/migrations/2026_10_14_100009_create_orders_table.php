<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique(); // UMC-YYMMDD-0001
            $table->string('source')->default('pos'); // pos|self_service|delivery
            $table->unsignedBigInteger('table_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('status')->default('confirmed'); // pending_confirmation|confirmed|preparing|ready|completed|void
            $table->string('payment_status')->default('unpaid'); // unpaid|paid|void
            $table->boolean('is_open_bill')->default(false);
            $table->text('open_bill_note')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['shift_id', 'created_at']);
            $table->index('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

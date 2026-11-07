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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->index();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('shipping_cost');
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voucher_code')->nullable();
            $table->json('shipping_address');
            $table->string('courier');
            $table->string('courier_service');
            $table->string('payment_method', 30);
            $table->timestamp('payment_due_at')->index();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

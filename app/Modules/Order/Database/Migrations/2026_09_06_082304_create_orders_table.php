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

            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cart_id')
                ->nullable()
                ->constrained('carts')
                ->nullOnDelete();

            $table->string('order_number', 50)->unique();

            $table->string('status', 30)
                ->default('pending');

            $table->string('payment_status', 30)
                ->default('pending');

            $table->string('fulfillment_status', 30)
                ->default('unfulfilled');

            $table->string('currency', 3)
                ->default('NPR');

            $table->decimal('subtotal', 15, 2)
                ->default(0);

            $table->decimal('discount_amount', 15, 2)
                ->default(0);

            $table->decimal('shipping_amount', 15, 2)
                ->default(0);

            $table->decimal('tax_amount', 15, 2)
                ->default(0);

            $table->decimal('total_amount', 15, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamp('placed_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('cart_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('fulfillment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
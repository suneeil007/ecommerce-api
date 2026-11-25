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

            $table->uuid('uuid')->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            /*
             * Product snapshot
             *
             * These values are intentionally stored in the order.
             * If the product name/variant/SKU changes later,
             * the historical order remains unchanged.
             */
            $table->string('product_name');

            $table->string('variant_name')
                ->nullable();

            $table->string('sku')
                ->nullable();

            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 15, 2);

            $table->decimal('discount_amount', 15, 2)
                ->default(0);

            $table->decimal('tax_amount', 15, 2)
                ->default(0);

            $table->decimal('total_price', 15, 2);

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
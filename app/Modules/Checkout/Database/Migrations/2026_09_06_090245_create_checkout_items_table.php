<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_items', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Checkout
            |--------------------------------------------------------------------------
            */

            $table->foreignId('checkout_id')
                ->constrained('checkouts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Product Snapshot
            |--------------------------------------------------------------------------
            */

            $table->string('product_name');

            $table->string('variant_name')
                ->nullable();

            $table->string('sku')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Quantity / Pricing
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 15, 2);

            $table->decimal('discount_amount', 15, 2)
                ->default(0);

            $table->decimal('tax_amount', 15, 2)
                ->default(0);

            $table->decimal('total_price', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('checkout_id');
            $table->index('product_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_items');
    }
};


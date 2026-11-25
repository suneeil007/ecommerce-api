<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            /*
             * Variant identification
             */
            $table->string('name', 191)
                ->nullable();

            $table->string('sku', 100)
                ->unique();

            $table->string('barcode', 100)
                ->nullable()
                ->unique();

            /*
             * Variant pricing
             */
            $table->decimal('price', 15, 2)
                ->nullable();

            $table->decimal('compare_at_price', 15, 2)
                ->nullable();

            $table->decimal('cost_price', 15, 2)
                ->nullable();

            /*
             * Variant inventory
             */
            $table->integer('stock')
                ->default(0);

            $table->integer('low_stock_threshold')
                ->default(5);

            /*
             * Physical information
             */
            $table->decimal('weight', 10, 3)
                ->nullable();

            /*
             * Variant image
             */
            $table->string('image', 500)
                ->nullable();

            /*
             * Status
             */
            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};


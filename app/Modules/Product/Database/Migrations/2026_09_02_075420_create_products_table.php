<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name', 191);

            $table->string('slug', 191)
                ->unique();

            $table->string('sku', 100)
                ->unique();

            $table->string('display_name', 191)
                ->nullable();

            $table->string('short_description', 500)
                ->nullable();

            $table->text('description')
                ->nullable();

            /*
             * Pricing
             */
            $table->decimal('price', 15, 2)
                ->default(0);

            $table->decimal('compare_at_price', 15, 2)
                ->nullable();

            $table->decimal('cost_price', 15, 2)
                ->nullable();

            /*
             * Inventory
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
             * Product status
             */
            $table->string('status', 30)
                ->default('draft');

            $table->boolean('is_featured')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            /*
             * Indexes
             */
            $table->index('category_id');
            $table->index('status');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


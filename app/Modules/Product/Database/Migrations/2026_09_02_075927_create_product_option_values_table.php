<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('product_option_id')
                ->constrained('product_options')
                ->cascadeOnDelete();

            $table->string('value', 191);

            $table->string('slug', 191);

            $table->string('display_value', 191)
                ->nullable();

            $table->string('color_code', 20)
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->unique([
                'product_option_id',
                'slug',
            ]);

            $table->index('product_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};


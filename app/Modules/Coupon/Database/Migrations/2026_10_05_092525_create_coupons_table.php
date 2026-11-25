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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            $table->string('code', 50)
                ->unique();

            $table->string('name');

            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            |
            | fixed      = Rs. 500 off
            | percentage = 10% off
            |
            */

            $table->enum('discount_type', [
                'fixed',
                'percentage',
            ]);

            $table->decimal('discount_value', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | Discount Limits
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'maximum_discount_amount',
                15,
                2
            )->nullable();

            $table->decimal(
                'minimum_order_amount',
                15,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Usage Limits
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('usage_limit')
                ->nullable();

            $table->unsignedInteger('usage_limit_per_user')
                ->nullable();

            $table->unsignedInteger('used_count')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Validity
            |--------------------------------------------------------------------------
            */

            $table->timestamp('starts_at')
                ->nullable();

            $table->timestamp('expires_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Coupon Rules
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            $table->boolean('is_first_order_only')
                ->default(false);

            $table->boolean('is_free_shipping')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            |
            | These are intentionally unsignedBigInteger without foreign keys
            | because the User module may have its own implementation.
            |
            */

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->unsignedBigInteger('updated_by')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('is_active');

            $table->index('discount_type');

            $table->index('starts_at');

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};


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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            |
            | user_id is NULL for guest customers.
            |
            */

            $table->unsignedBigInteger('user_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Guest Identification
            |--------------------------------------------------------------------------
            |
            | Used when the customer is not authenticated.
            |
            */

            $table->string('guest_token', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            |
            | This remains nullable because an order may not yet exist
            | when the coupon is being validated.
            |
            */

            $table->unsignedBigInteger('order_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Actual Discount
            |--------------------------------------------------------------------------
            |
            | Stores the exact discount that was applied to the order.
            |
            */

            $table->decimal(
                'discount_amount',
                15,
                2
            );

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('user_id');

            $table->index('guest_token');

            $table->index('order_id');

            $table->index([
                'coupon_id',
                'user_id',
            ]);

            $table->index([
                'coupon_id',
                'guest_token',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Coupon Usage Per Order
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'coupon_id',
                'order_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};


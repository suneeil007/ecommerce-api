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
        Schema::table('orders', function (Blueprint $table) {

            $table->string('customer_name', 255)
                ->nullable()
                ->after('cart_id');

            $table->string('phone', 30)
                ->nullable()
                ->after('customer_name');

            $table->string('email', 255)
                ->nullable()
                ->after('phone');

            $table->string('district', 100)
                ->nullable()
                ->after('email');

            $table->string('city', 150)
                ->nullable()
                ->after('district');

            $table->string('address', 500)
                ->nullable()
                ->after('city');

            $table->string('payment_method', 50)
                ->nullable()
                ->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn([
                'customer_name',
                'phone',
                'email',
                'district',
                'city',
                'address',
                'payment_method',
            ]);
        });
    }
};
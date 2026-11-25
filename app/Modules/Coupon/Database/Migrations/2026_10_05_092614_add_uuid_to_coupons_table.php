<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->uuid('uuid')
                ->nullable()
                ->unique()
                ->after('id');
        });

        /*
        |--------------------------------------------------------------------------
        | Generate UUIDs for Existing Coupons
        |--------------------------------------------------------------------------
        */

        DB::table('coupons')
            ->orderBy('id')
            ->chunkById(100, function ($coupons) {
                foreach ($coupons as $coupon) {
                    DB::table('coupons')
                        ->where('id', $coupon->id)
                        ->update([
                            'uuid' => (string) Str::uuid(),
                        ]);
                }
            });

        /*
        |--------------------------------------------------------------------------
        | Make UUID Required
        |--------------------------------------------------------------------------
        */

        Schema::table('coupons', function (Blueprint $table) {
            $table->uuid('uuid')
                ->nullable(false)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique([
                'uuid',
            ]);

            $table->dropColumn('uuid');
        });
    }
};


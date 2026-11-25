
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')
                ->nullable()
                ->after('user_id');

            $table->string('province', 150)
                ->nullable()
                ->after('province_id');

            $table->unsignedBigInteger('district_id')
                ->nullable()
                ->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'district_id',
                'province',
                'province_id',
            ]);
        });
    }
};

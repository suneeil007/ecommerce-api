<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->foreign('attribute_id')
                ->references('id')
                ->on('attributes')
                ->restrictOnDelete();

            $table->index('attribute_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropForeign([
                'attribute_id',
            ]);

            $table->dropIndex([
                'attribute_id',
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add the column first.
        if (! Schema::hasColumn('product_option_values', 'attribute_value_id')) {
            Schema::table('product_option_values', function (Blueprint $table) {
                $table->foreignId('attribute_value_id')
                    ->nullable()
                    ->after('product_option_id');
            });
        }

        // 2. Map existing product option values to master attribute values.
        DB::statement('
            UPDATE product_option_values pov
            INNER JOIN product_options po
                ON po.id = pov.product_option_id
            INNER JOIN attribute_values av
                ON av.attribute_id = po.attribute_id
                AND av.value = pov.value
            SET pov.attribute_value_id = av.id
        ');

        // 3. Make sure every existing row was successfully mapped.
        $unmapped = DB::table('product_option_values')
            ->whereNull('attribute_value_id')
            ->count();

        if ($unmapped > 0) {
            throw new RuntimeException(
                "Unable to map {$unmapped} product option value(s) to attribute values."
            );
        }

        // 4. Make the relationship required.
        Schema::table('product_option_values', function (Blueprint $table) {
            $table->foreignId('attribute_value_id')
                ->nullable(false)
                ->change();
        });

        // 5. Add foreign key and index.
        Schema::table('product_option_values', function (Blueprint $table) {
            $table->foreign('attribute_value_id')
                ->references('id')
                ->on('attribute_values')
                ->restrictOnDelete();

            $table->index('attribute_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_option_values', function (Blueprint $table) {
            $table->dropForeign(['attribute_value_id']);
            $table->dropIndex(['attribute_value_id']);
            $table->dropColumn('attribute_value_id');
        });
    }
};
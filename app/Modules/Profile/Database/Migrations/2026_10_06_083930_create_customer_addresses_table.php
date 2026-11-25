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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('label', 50)
                ->nullable();

            $table->string('recipient_name');

            $table->string('phone', 30);

            $table->string('district', 100);

            $table->string('city', 100);

            $table->string('area', 150)
                ->nullable();

            $table->string('street_address', 255);

            $table->string('landmark', 255)
                ->nullable();

            $table->boolean('is_default')
                ->default(false);

            $table->boolean('is_confirmed')
                ->default(false);

            $table->timestamps();

            $table->index([
                'user_id',
                'is_default',
            ]);

            $table->index([
                'user_id',
                'is_confirmed',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};

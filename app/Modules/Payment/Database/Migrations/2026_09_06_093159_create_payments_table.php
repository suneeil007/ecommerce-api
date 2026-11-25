<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('method', 50);
            $table->string('status', 50)->default('pending');

            $table->string('currency', 10)->default('NPR');

            $table->decimal('amount', 15, 2);

            $table->string('reference', 191)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index('method');
            $table->index('status');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};


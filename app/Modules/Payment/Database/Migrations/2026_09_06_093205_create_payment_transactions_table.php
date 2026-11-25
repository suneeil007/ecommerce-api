<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('provider', 50);
            $table->string('transaction_id', 191)->nullable();

            $table->string('status', 50)->default('pending');

            $table->decimal('amount', 15, 2);

            $table->string('currency', 10)->default('NPR');

            $table->json('response_data')->nullable();

            $table->text('failure_reason')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('payment_id');
            $table->index('provider');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};


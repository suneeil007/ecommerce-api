<?php

namespace App\Modules\Payment\Models;

use App\Modules\Payment\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'uuid',
        'payment_id',
        'provider',
        'transaction_id',
        'status',
        'amount',
        'currency',
        'response_data',
        'failure_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'response_data' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentTransaction $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    public static function findByUuid(
        string $uuid
    ): ?self {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            Payment::class
        );
    }
}


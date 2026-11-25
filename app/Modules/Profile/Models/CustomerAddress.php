<?php

namespace App\Modules\Profile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerAddress extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    protected $table = 'customer_addresses';

    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uuid',
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'district',
        'city',
        'area',
        'street_address',
        'landmark',
        'is_default',
        'is_confirmed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_confirmed' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Booted
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (CustomerAddress $address) {
            if (empty($address->uuid)) {
                $address->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Route Key
    |--------------------------------------------------------------------------
    */

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Find By UUID
    |--------------------------------------------------------------------------
    */

    public static function findByUuid(
        string $uuid
    ): ?self {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }
}

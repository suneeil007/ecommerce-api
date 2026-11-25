<?php

namespace App\Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    /*
    |--------------------------------------------------------------------------
    | Province Districts
    |--------------------------------------------------------------------------
    */

    public function districts(): HasMany
    {
        return $this->hasMany(
            District::class,
            'province_id'
        );
    }
}

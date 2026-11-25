<?php

namespace App\Modules\Attribute\Repositories;

use App\Modules\Attribute\Repositories\Contracts\AttributeRepositoryInterface;
use App\Modules\Attribute\Models\Attribute;
use App\Modules\Attribute\Models\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttributeRepository implements AttributeRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    public function getAll(
        int $perPage = 15
    ): LengthAwarePaginator {
        return Attribute::query()
            ->with('values')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findByUuid(
        string $uuid
    ): ?Attribute {
        return Attribute::query()
            ->with('values')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(
        array $data
    ): Attribute {
        return Attribute::create($data);
    }

    public function update(
        Attribute $attribute,
        array $data
    ): Attribute {
        $attribute->update($data);

        return $attribute->fresh('values');
    }

    public function delete(
        Attribute $attribute
    ): bool {
        return (bool) $attribute->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute Values
    |--------------------------------------------------------------------------
    */

    public function getValues(
        Attribute $attribute,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $attribute
            ->values()
            ->paginate($perPage);
    }

    public function findValueByUuid(
        string $uuid
    ): ?AttributeValue {
        return AttributeValue::query()
            ->with('attribute')
            ->where('uuid', $uuid)
            ->first();
    }

    public function createValue(
        Attribute $attribute,
        array $data
    ): AttributeValue {
        return $attribute->values()->create($data);
    }

    public function updateValue(
        AttributeValue $value,
        array $data
    ): AttributeValue {
        $value->update($data);

        return $value->fresh('attribute');
    }

    public function deleteValue(
        AttributeValue $value
    ): bool {
        return (bool) $value->delete();
    }
}


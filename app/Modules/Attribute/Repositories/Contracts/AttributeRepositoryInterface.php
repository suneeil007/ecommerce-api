<?php

namespace App\Modules\Attribute\Repositories\Contracts;

use App\Modules\Attribute\Models\Attribute;
use App\Modules\Attribute\Models\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttributeRepositoryInterface
{
    public function getAll(int $perPage = 15): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Attribute;

    public function create(array $data): Attribute;

    public function update(
        Attribute $attribute,
        array $data
    ): Attribute;

    public function delete(Attribute $attribute): bool;

    public function getValues(
        Attribute $attribute,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function findValueByUuid(
        string $uuid
    ): ?AttributeValue;

    public function createValue(
        Attribute $attribute,
        array $data
    ): AttributeValue;

    public function updateValue(
        AttributeValue $value,
        array $data
    ): AttributeValue;

    public function deleteValue(
        AttributeValue $value
    ): bool;
}


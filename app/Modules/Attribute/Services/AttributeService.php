<?php

namespace App\Modules\Attribute\Services;

use App\Modules\Attribute\Repositories\Contracts\AttributeRepositoryInterface;
use App\Modules\Attribute\Models\Attribute;
use App\Modules\Attribute\Models\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttributeService
{
    public function __construct(
        protected AttributeRepositoryInterface $attributeRepository
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    public function getAll(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->attributeRepository->getAll($perPage);
    }

    public function findByUuid(
        string $uuid
    ): ?Attribute {
        return $this->attributeRepository->findByUuid($uuid);
    }

    public function create(
        array $data
    ): Attribute {
        return $this->attributeRepository->create($data);
    }

    public function update(
        Attribute $attribute,
        array $data
    ): Attribute {
        return $this->attributeRepository->update(
            $attribute,
            $data
        );
    }

    public function delete(
        Attribute $attribute
    ): bool {
        return $this->attributeRepository->delete($attribute);
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
        return $this->attributeRepository->getValues(
            $attribute,
            $perPage
        );
    }

    public function findValueByUuid(
        string $uuid
    ): ?AttributeValue {
        return $this->attributeRepository->findValueByUuid($uuid);
    }

    public function createValue(
        Attribute $attribute,
        array $data
    ): AttributeValue {
        return $this->attributeRepository->createValue(
            $attribute,
            $data
        );
    }

    public function updateValue(
        AttributeValue $value,
        array $data
    ): AttributeValue {
        return $this->attributeRepository->updateValue(
            $value,
            $data
        );
    }

    public function deleteValue(
        AttributeValue $value
    ): bool {
        return $this->attributeRepository->deleteValue(
            $value
        );
    }
}


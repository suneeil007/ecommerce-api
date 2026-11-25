<?php

namespace App\Modules\Permission\Repositories\Contracts;

use App\Modules\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

interface PermissionRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): Permission;

    public function create(array $data): Permission;

    public function update(int $id, array $data): Permission;

    public function delete(int $id): bool;
}


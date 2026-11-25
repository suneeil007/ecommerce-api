<?php

namespace App\Modules\User\Repositories;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    private const CUSTOMER_ROLE = 'customer';

    private function customerQuery()
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', self::CUSTOMER_ROLE);
            })
            ->with([
                'roles:id,name,display_name',
                'customerProfile',
                'customerAddresses',
            ])
            ->withCount([
                'orders as total_orders',

                'orders as paid_orders' => function ($query) {
                    $query->where('payment_status', 'paid');
                },

                'orders as pending_orders' => function ($query) {
                    $query->where('payment_status', 'pending');
                },

                'orders as partially_paid_orders' => function ($query) {
                    $query->where('payment_status', 'partially_paid');
                },

                'orders as cancelled_orders' => function ($query) {
                    $query->where('status', 'cancelled');
                },
            ])
            ->withSum([
                'orders as total_spent' => function ($query) {
                    $query->where('payment_status', 'paid');
                },
            ], 'total_amount');
    }

    public function paginateCustomers(array $filters = []): LengthAwarePaginator
    {
        $query = $this->customerQuery();

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhereHas('customerProfile', function ($profileQuery) use ($search) {
                        $profileQuery->where('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('customerProfile', function ($profileQuery) use ($filters) {
                $profileQuery->where('gender', $filters['gender']);
            });
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('users.is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('users.is_active', false);
            }
        }

        return $query
            ->latest('users.id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function findCustomerByUuid(string $uuid): User
    {
        return $this->customerQuery()
            ->where('users.uuid', $uuid)
            ->firstOrFail();
    }

    public function updateCustomer(string $uuid, array $data): User
    {
        return DB::transaction(function () use ($uuid, $data) {
            $user = $this->findCustomerByUuid($uuid);

            $userData = [];

            if (array_key_exists('name', $data)) {
                $userData['name'] = $data['name'];
            }

            if (array_key_exists('email', $data)) {
                $userData['email'] = $data['email'];
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            $profileFields = [
                'phone',
                'date_of_birth',
                'gender',
            ];

            $profileData = [];

            foreach ($profileFields as $field) {
                if (array_key_exists($field, $data)) {
                    $profileData[$field] = $data[$field];
                }
            }

            if (!empty($profileData)) {
                $user->customerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
                );
            }

            return $this->findCustomerByUuid($uuid);
        });
    }

    public function updateCustomerStatus(
        string $uuid,
        bool $isActive
    ): User {
        return DB::transaction(function () use ($uuid, $isActive) {
            $user = $this->findCustomerByUuid($uuid);

            $user->update([
                'is_active' => $isActive,
            ]);

            if (!$isActive) {
                // Immediately revoke all existing customer tokens.
                $user->tokens()->delete();
            }

            return $this->findCustomerByUuid($uuid);
        });
    }

    public function deleteCustomer(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $user = $this->findCustomerByUuid($uuid);

            // Revoke tokens before soft deletion.
            $user->tokens()->delete();

            return (bool) $user->delete();
        });
    }

    public function paginateCustomerOrders(
        string $uuid,
        array $filters = []
    ): LengthAwarePaginator {
        $customer = $this->findCustomerByUuid($uuid);

        $query = $customer->orders()
            ->with([
                'items',
                'payments',
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where(
                'payment_status',
                $filters['payment_status']
            );
        }

        return $query
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }
}

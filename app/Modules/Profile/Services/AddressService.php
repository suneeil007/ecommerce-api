<?php

namespace App\Modules\Profile\Services;

use App\Models\User;
use App\Modules\Profile\Models\CustomerAddress;
use App\Modules\Profile\Repositories\Contracts\AddressRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function __construct(
        protected AddressRepositoryInterface $addressRepository
    ) {
    }

    public function getAddresses(
        User $user
    ): Collection {
        return $this->addressRepository->getByUserId(
            $user->id
        );
    }

    public function getDefaultAddress(
        User $user
    ): ?CustomerAddress {
        return $this->addressRepository->getDefault(
            $user->id
        );
    }

    public function findByUuid(
        User $user,
        string $uuid
    ): CustomerAddress {
        $address = $this->addressRepository->findByUserAndUuid(
            $user->id,
            $uuid
        );

        if (!$address) {
            throw (new ModelNotFoundException())
                ->setModel(
                    CustomerAddress::class,
                    [$uuid]
                );
        }

        return $address;
    }

    public function createAddress(
        User $user,
        array $data
    ): CustomerAddress {
        return DB::transaction(function () use (
            $user,
            $data
        ) {
            $hasAddresses = $user->customerAddresses()
                ->exists();

            $requestedDefault = (bool) (
                $data['is_default'] ?? false
            );

            /*
             * The first address must always be the default.
             *
             * If this is not the first address, only make it
             * default when the customer explicitly requests it.
             */
            $makeDefault = !$hasAddresses
                || $requestedDefault;

            if ($makeDefault) {
                $this->addressRepository->clearDefault(
                    $user->id
                );
            }

            $data['is_default'] = $makeDefault;

            return $this->addressRepository->create(
                $user,
                $data
            );
        });
    }

    public function updateAddress(
        User $user,
        string $uuid,
        array $data
    ): CustomerAddress {
        return DB::transaction(function () use (
            $user,
            $uuid,
            $data
        ) {
            $address = $this->findByUuid(
                $user,
                $uuid
            );

            /*
             * No default field supplied:
             *
             * Keep the existing default state unchanged.
             */
            if (!array_key_exists('is_default', $data)) {
                return $this->addressRepository->update(
                    $address,
                    $data
                );
            }

            $requestedDefault = (bool) $data['is_default'];

            /*
             * Address is being made default.
             */
            if ($requestedDefault) {
                $this->addressRepository->clearDefault(
                    $user->id
                );

                $data['is_default'] = true;

                return $this->addressRepository->update(
                    $address,
                    $data
                );
            }

            /*
             * Address is already default and the customer is
             * trying to unset it.
             *
             * Do not allow the customer to end up with zero
             * default addresses.
             */
            if ($address->is_default) {
                $otherAddress = $user->customerAddresses()
                    ->whereKeyNot($address->id)
                    ->orderByDesc('created_at')
                    ->first();

                /*
                 * If another address exists, keep the current
                 * address as default.
                 */
                if ($otherAddress) {
                    $data['is_default'] = true;

                    return $this->addressRepository->update(
                        $address,
                        $data
                    );
                }

                /*
                 * This is the customer's only address.
                 * It must remain the default.
                 */
                $data['is_default'] = true;

                return $this->addressRepository->update(
                    $address,
                    $data
                );
            }

            /*
             * Address is already non-default.
             *
             * Since is_default=false was explicitly requested,
             * simply keep it false.
             */
            $data['is_default'] = false;

            return $this->addressRepository->update(
                $address,
                $data
            );
        });
    }

    public function setDefaultAddress(
        User $user,
        string $uuid
    ): CustomerAddress {
        return DB::transaction(function () use (
            $user,
            $uuid
        ) {
            $address = $this->findByUuid(
                $user,
                $uuid
            );

            $this->addressRepository->clearDefault(
                $user->id
            );

            return $this->addressRepository->setDefault(
                $address
            );
        });
    }

    public function deleteAddress(
        User $user,
        string $uuid
    ): bool {
        return DB::transaction(function () use (
            $user,
            $uuid
        ) {
            $address = $this->findByUuid(
                $user,
                $uuid
            );

            $wasDefault = (bool) $address->is_default;

            $deleted = $this->addressRepository->delete(
                $address
            );

            if (!$deleted) {
                return false;
            }

            /*
             * If the deleted address was the default,
             * automatically promote another address.
             */
            if ($wasDefault) {
                $replacement = $user->customerAddresses()
                    ->orderByDesc('created_at')
                    ->first();

                if ($replacement) {
                    $this->addressRepository->clearDefault(
                        $user->id
                    );

                    $this->addressRepository->setDefault(
                        $replacement
                    );
                }
            }

            return true;
        });
    }
}

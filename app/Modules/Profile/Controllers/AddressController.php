<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Requests\StoreAddressRequest;
use App\Modules\Profile\Requests\UpdateAddressRequest;
use App\Modules\Profile\Resources\AddressResource;
use App\Modules\Profile\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        protected AddressService $addressService
    ) {
    }

    /**
     * Get all addresses for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $this->addressService->getAddresses(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Addresses retrieved successfully.',
            'data' => AddressResource::collection($addresses),
        ]);
    }

    /**
     * Get a single customer address.
     */
    public function show(
        Request $request,
        string $uuid
    ): JsonResponse {
        $address = $this->addressService->findByUuid(
            $request->user(),
            $uuid
        );

        return response()->json([
            'success' => true,
            'message' => 'Address retrieved successfully.',
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Create a new customer address.
     */
    public function store(
        StoreAddressRequest $request
    ): JsonResponse {
        $address = $this->addressService->createAddress(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'data' => new AddressResource($address),
        ], 201);
    }

    /**
     * Update an existing customer address.
     */
    public function update(
        UpdateAddressRequest $request,
        string $uuid
    ): JsonResponse {
        $address = $this->addressService->updateAddress(
            $request->user(),
            $uuid,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Set an address as the customer's default address.
     */
    public function setDefault(
        Request $request,
        string $uuid
    ): JsonResponse {
        $address = $this->addressService->setDefaultAddress(
            $request->user(),
            $uuid
        );

        return response()->json([
            'success' => true,
            'message' => 'Default address updated successfully.',
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Delete a customer address.
     */
    public function destroy(
        Request $request,
        string $uuid
    ): JsonResponse {
        $this->addressService->deleteAddress(
            $request->user(),
            $uuid
        );

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
            'data' => null,
        ]);
    }
}

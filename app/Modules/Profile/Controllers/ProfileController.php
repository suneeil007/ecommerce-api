<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Requests\UpdateProfileRequest;
use App\Modules\Profile\Resources\ProfileResource;
use App\Modules\Profile\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {
    }

    /**
     * Get the authenticated customer's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $this->profileService->getProfile(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => new ProfileResource($profile),
        ]);
    }

    /**
     * Update the authenticated customer's profile.
     */
    public function update(
        UpdateProfileRequest $request
    ): JsonResponse {
        $profile = $this->profileService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new ProfileResource($profile),
        ]);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::prefix('locations')->group(function () {
    // Get all provinces
    Route::get('/provinces', function () {
        return response()->json([
            'success' => true,
            'message' => 'Provinces retrieved successfully.',
            'data' => get_provinces(),
        ]);
    });

    // Get districts by province ID
    Route::get('/provinces/{provinceId}/districts', function (int $provinceId) {
        $provinceExists = DB::table('provinces')
            ->where('id', $provinceId)
            ->exists();

        if (! $provinceExists) {
            return response()->json([
                'success' => false,
                'message' => 'Province not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Districts retrieved successfully.',
            'data' => get_districts_by_province($provinceId),
        ]);
    });
});
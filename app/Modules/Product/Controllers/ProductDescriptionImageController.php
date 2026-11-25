<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\UploadDescriptionImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductDescriptionImageController extends Controller
{
    /**
     * Upload an image used inside the product description.
     */
    public function store(
        UploadDescriptionImageRequest $request
    ): JsonResponse {
        $path = $this->storeDescriptionImage(
            $request->file('upload')
        );

        $baseUrl = rtrim(
            config('app.url'),
            '/'
        );

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'data' => [
                'url' => $baseUrl . '/' . $path,
                'path' => $path,
            ],
        ]);
    }

    /**
     * Store product description image.
     */
    protected function storeDescriptionImage(
        UploadedFile $file
    ): string {
        $filename =
            Str::slug(
                pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                )
            )
            . '-'
            . Str::random(20)
            . '.'
            . $file->getClientOriginalExtension();

        $directory =
            public_path(
                'products/descriptions'
            );

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0755,
                true
            );
        }

        $file->move(
            $directory,
            $filename
        );

        return 'products/descriptions/'
            . $filename;
    }
}
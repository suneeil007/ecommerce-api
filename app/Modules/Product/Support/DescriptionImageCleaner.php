<?php

namespace App\Modules\Product\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DescriptionImageCleaner
{
    /**
     * Get the filenames of description images used in the HTML.
     */
    public static function extract(?string $html): array
    {
        if (!$html) {
            return [];
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);

        return collect($matches[1])
            ->map(fn ($url) => parse_url($url, PHP_URL_PATH))
            ->filter(fn ($path) => $path && str_contains($path, 'products/descriptions/'))
            ->map(fn ($path) => basename($path)) // basename prevents path traversal
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Delete the given description image files.
     */
    public static function delete(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $path = public_path('products/descriptions/' . $filename);

            $existed = file_exists($path);
            $deleted = $existed ? File::delete($path) : false;

            Log::info('delete description image', [
                'path' => $path,
                'exists' => $existed,
                'deleted' => $deleted,
            ]);
        }
    }
}
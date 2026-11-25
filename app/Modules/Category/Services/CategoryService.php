<?php

namespace App\Modules\Category\Services;

use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Frontend
    |--------------------------------------------------------------------------
    */

    public function getCategories(): Collection
    {
        return $this->categoryRepository
            ->getRootCategories();
    }


    public function getBySlug(string $slug): ?Category
    {
        return $this->categoryRepository
            ->findBySlug($slug);
    }


    public function getChildren(
        Category $category
    ): Collection {
        return $this->categoryRepository
            ->getChildren($category);
    }


    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    public function adminIndex(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->categoryRepository
            ->paginate($perPage);
    }


    public function getByUuid(
        string $uuid
    ): ?Category {
        return $this->categoryRepository
            ->findByUuid($uuid);
    }


    /**
     * Create category.
     */
    public function create(array $data): Category
    {
        /*
         * Handle image upload.
         */
        if (
            isset($data['image']) &&
            $data['image'] instanceof UploadedFile
        ) {
            $data['image'] = $this->uploadImage(
                $data['image']
            );
        }

        return $this->categoryRepository
            ->create($data);
    }


    /**
     * Update category.
     */
    public function update(
        Category $category,
        array $data
    ): Category {
        /*
         * Handle new image.
         */
        if (
            isset($data['image']) &&
            $data['image'] instanceof UploadedFile
        ) {
            /*
             * Delete old image first.
             */
            $this->deleteImage(
                $category->image
            );

            /*
             * Upload new image.
             */
            $data['image'] = $this->uploadImage(
                $data['image']
            );
        }

        return $this->categoryRepository
            ->update(
                $category,
                $data
            );
    }


    /**
     * Delete category.
     */
    public function delete(
        Category $category
    ): bool {
        /*
         * Delete category image.
         */
        $this->deleteImage(
            $category->image
        );

        return $this->categoryRepository
            ->delete($category);
    }


    /*
    |--------------------------------------------------------------------------
    | Image Handling
    |--------------------------------------------------------------------------
    */

    /**
     * Upload category image.
     *
     * Physical location:
     *
     * public/category/
     *
     * Database:
     *
     * category/filename.jpg
     */
    protected function uploadImage(
        UploadedFile $image
    ): string {
        $directory = public_path('category');

        /*
        |--------------------------------------------------------------------------
        | Create directory if it does not exist
        |--------------------------------------------------------------------------
        */

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0755,
                true
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get original filename without extension
        |--------------------------------------------------------------------------
        |
        | Example:
        | banner eco concern.jpg
        |
        | Result:
        | banner eco concern
        |
        */

        $originalName = pathinfo(
            $image->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        /*
        |--------------------------------------------------------------------------
        | Clean original filename
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | banner eco concern
        |
        | becomes:
        |
        | banner-eco-concern
        |
        */

        $cleanName = Str::slug(
            $originalName
        );

        /*
        |--------------------------------------------------------------------------
        | Generate 20-character unique suffix
        |--------------------------------------------------------------------------
        */

        $uniqueId = Str::lower(
            Str::random(20)
        );

        /*
        |--------------------------------------------------------------------------
        | Get extension
        |--------------------------------------------------------------------------
        */

        $extension = strtolower(
            $image->getClientOriginalExtension()
        );

        /*
        |--------------------------------------------------------------------------
        | Build final filename
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | banner-eco-concern-a8f42c91e73b5d604812.jpg
        |
        */

        $filename = $cleanName
            . '-'
            . $uniqueId
            . '.'
            . $extension;

        /*
        |--------------------------------------------------------------------------
        | Move image
        |--------------------------------------------------------------------------
        */

        $image->move(
            $directory,
            $filename
        );

        /*
        |--------------------------------------------------------------------------
        | Store relative path in database
        |--------------------------------------------------------------------------
        */

        return 'category/' . $filename;
    }


    /**
     * Delete category image.
     */
    protected function deleteImage(
        ?string $image
    ): void {
        if (!$image) {
            return;
        }

        /*
         * Convert stored relative path
         * into physical public path.
         */
        $path = public_path(
            $image
        );

        if (
            file_exists($path) &&
            is_file($path)
        ) {
            unlink($path);
        }
    }
}


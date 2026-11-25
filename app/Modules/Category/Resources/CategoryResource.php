<?php

namespace App\Modules\Category\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'uuid' => $this->uuid,

            'parent_id' => $this->parent_id,

            'name' => $this->name,

            'slug' => $this->slug,

            'display_name' => $this->display_name,

            'description' => $this->description,

            /*
             * Store/return relative image path only.
             *
             * Example:
             * category/category_68b5xxxx.jpg
             */
            'image' => $this->image,

            'is_active' => $this->is_active,

            'sort_order' => $this->sort_order,

            'children' => CategoryResource::collection(
                $this->whenLoaded('children')
            ),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}
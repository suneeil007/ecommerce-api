<?php

namespace App\Modules\Attribute\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'attribute_id' => $this->attribute_id,
            'value' => $this->value,
            'slug' => $this->slug,
            'display_value' => $this->display_value,
            'sort_order' => $this->sort_order,

            'attribute' => new AttributeResource(
                $this->whenLoaded('attribute')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


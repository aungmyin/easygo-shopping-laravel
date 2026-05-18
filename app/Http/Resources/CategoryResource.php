<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image'       => $this->image ? Storage::url($this->image) : null,
            'is_active'   => $this->is_active,
            'sort_order'  => $this->sort_order,
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}

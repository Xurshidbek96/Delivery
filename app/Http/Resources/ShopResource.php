<?php

namespace App\Http\Resources;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Shop */
class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'image_url' => $this->imageUrl(),
            'products_count' => $this->whenCounted('products'),
        ];
    }
}

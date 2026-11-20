<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'name' => $this->name,
            'description' => $this->description,
            'unit' => ['value' => $this->unit->value, 'label' => $this->unit->label()],
            'price' => $this->price,
            'image_url' => $this->imageUrl(),
            'shop' => $this->whenLoaded('shop', fn () => ['id' => $this->shop->id, 'name' => $this->shop->name, 'address' => $this->shop->address]),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'shop_name' => $this->shop_name,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => ['value' => $this->unit->value, 'label' => $this->unit->label()],
            'note' => $this->note,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'price' => $this->price,
            'courier_note' => $this->courier_note,
        ];
    }
}

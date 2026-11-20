<?php

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Address */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'address' => $this->address,
            'entrance' => $this->entrance,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'landmark' => $this->landmark,
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
}

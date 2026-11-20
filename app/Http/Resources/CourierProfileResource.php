<?php

namespace App\Http\Resources;

use App\Models\CourierProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourierProfile */
class CourierProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'vehicle' => ['value' => $this->vehicle->value, 'label' => $this->vehicle->label()],
            'vehicle_number' => $this->vehicle_number,
            'is_online' => $this->is_online,
            'last_location_at' => $this->last_location_at?->toIso8601String(),
        ];
    }
}

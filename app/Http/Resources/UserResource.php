<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'role' => $this->role->value,
            // Faqat kuryerda: transport va onlayn holati
            'courier' => $this->when(
                $this->role === UserRole::Courier,
                fn () => new CourierProfileResource($this->courierProfile),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

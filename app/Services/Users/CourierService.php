<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CourierService
{
    /** @param array{name: string, phone: string, password?: ?string, vehicle: string, vehicle_number?: ?string, note?: ?string, is_active: bool} $data */
    public function save(array $data, ?User $courier = null): User
    {
        return DB::transaction(function () use ($data, $courier) {
            $courier ??= new User(['role' => UserRole::Courier]);

            $courier->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'is_active' => $data['is_active'],
            ]);
            if (! empty($data['password'])) {
                $courier->password = $data['password'];
            }
            $courier->save();

            $courier->courierProfile()->updateOrCreate([], [
                'vehicle' => $data['vehicle'],
                'vehicle_number' => $data['vehicle_number'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Parol almashtirilsa yoki bloklansa — ilovadagi sessiyalar yopiladi
            if (! $courier->wasRecentlyCreated && (! empty($data['password']) || ! $courier->is_active)) {
                $courier->tokens()->delete();
            }

            return $courier;
        });
    }
}

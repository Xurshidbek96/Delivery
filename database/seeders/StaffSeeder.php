<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Birinchi administrator. Kirgandan keyin parolni darhol almashtiring (Profil sahifasi). */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['phone' => '+998900000001'],
            ['name' => 'Administrator', 'role' => UserRole::Admin, 'password' => 'admin12345', 'is_active' => true],
        );
    }
}

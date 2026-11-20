<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production'da faqat: php artisan db:seed --class=StaffSeeder
     * Test rejimi (demo ma'lumot bilan): php artisan db:seed
     */
    public function run(): void
    {
        $this->call([StaffSeeder::class, DemoSeeder::class]);
    }
}

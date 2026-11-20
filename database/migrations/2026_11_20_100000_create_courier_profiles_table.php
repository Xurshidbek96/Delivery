<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('vehicle', 20)->default('foot');
            $table->string('vehicle_number', 20)->nullable();
            $table->boolean('is_online')->default(false)->index();
            // Oxirgi joylashuv — keyinchalik "eng yaqin kuryer" avtomatik tanlovi uchun
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};

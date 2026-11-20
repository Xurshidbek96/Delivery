<?php

namespace App\Models;

use App\Enums\CourierVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierProfile extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle',
        'vehicle_number',
        'is_online',
        'last_lat',
        'last_lng',
        'last_location_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'vehicle' => CourierVehicle::class,
            'is_online' => 'boolean',
            'last_lat' => 'float',
            'last_lng' => 'float',
            'last_location_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasLocation(): bool
    {
        return $this->last_lat !== null && $this->last_lng !== null;
    }
}

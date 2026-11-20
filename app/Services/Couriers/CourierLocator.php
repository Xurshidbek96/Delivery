<?php

namespace App\Services\Couriers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Collection;

/**
 * Buyurtma uchun kuryer nomzodlari. Hozir moderator ro'yxatdan qo'lda tanlaydi.
 * Keyin avtomatik taqsimlash: shu ro'yxatning birinchisini (eng yaqin bo'sh kuryer) olish kifoya.
 *
 * Tartib: onlayn → bo'sh joyi bor → kamroq band → yaqinroq.
 */
class CourierLocator
{
    public function __construct(private readonly SettingsRepository $settings) {}

    /** @return Collection<int, User> har biriga active_orders_count, distance_km, has_capacity qo'shilgan */
    public function candidatesFor(Order $order): Collection
    {
        $max = $this->settings->courierMaxActiveOrders();
        $activeStatuses = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::courierActive());

        return User::query()
            ->role(UserRole::Courier)
            ->active()
            ->has('courierProfile')
            ->with('courierProfile')
            ->withCount(['courierOrders as active_orders_count' => fn ($q) => $q->whereIn('status', $activeStatuses)])
            ->get()
            ->each(function (User $courier) use ($order, $max) {
                $profile = $courier->courierProfile;
                $courier->setAttribute('distance_km', $profile->hasLocation() && $order->lat !== null
                    ? round($this->distanceKm($order->lat, $order->lng, $profile->last_lat, $profile->last_lng), 1)
                    : null);
                $courier->setAttribute('has_capacity', $courier->active_orders_count < $max);
            })
            ->sortBy([
                fn (User $a, User $b) => $b->courierProfile->is_online <=> $a->courierProfile->is_online,
                fn (User $a, User $b) => $b->has_capacity <=> $a->has_capacity,
                fn (User $a, User $b) => $a->active_orders_count <=> $b->active_orders_count,
                fn (User $a, User $b) => ($a->distance_km ?? PHP_INT_MAX) <=> ($b->distance_km ?? PHP_INT_MAX),
            ])
            ->values();
    }

    /** Haversine formulasi, km */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

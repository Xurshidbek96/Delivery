<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Admin paneldan boshqariladigan sozlamalar. Standart qiymatlar config('delivery.defaults') da,
 * o'zgartirilganlari `settings` jadvalida. Har doim shu klass orqali o'qing.
 */
class SettingsRepository
{
    private const CACHE_KEY = 'delivery:settings';

    /** @return array<string, mixed> */
    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());

        return array_merge(config('delivery.defaults'), $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @param array<string, mixed> $values */
    public function set(array $values): void
    {
        $known = array_keys(config('delivery.defaults'));

        foreach ($values as $key => $value) {
            if (! in_array($key, $known, true)) {
                continue;
            }
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function deliveryFee(): int
    {
        return (int) $this->get('delivery_fee');
    }

    public function serviceFee(): int
    {
        return (int) $this->get('service_fee');
    }

    public function courierMaxActiveOrders(): int
    {
        return max(1, (int) $this->get('courier_max_active_orders'));
    }

    public function acceptingOrders(): bool
    {
        return (bool) $this->get('accepting_orders');
    }

    public function supportPhone(): string
    {
        return (string) $this->get('support_phone');
    }

    public function maxItemsPerOrder(): int
    {
        return max(1, (int) $this->get('max_items_per_order'));
    }
}

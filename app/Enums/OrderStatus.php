<?php

namespace App\Enums;

/**
 * Buyurtma holatlari va ruxsat etilgan o'tishlar grafi.
 * Kim qaysi o'tishni bajara olishi — Actions/Orders/* da (har bir amal o'z aktyorini biladi).
 * Batafsil: docs/order-status-flow.md
 */
enum OrderStatus: string
{
    case Pending = 'pending';         // Mijoz yubordi, moderator tasdig'ini kutmoqda
    case Confirmed = 'confirmed';     // Moderator tasdiqladi, kuryer tanlanmagan
    case Assigned = 'assigned';       // Kuryer biriktirildi, kuryer qabul qilishini kutmoqda
    case Shopping = 'shopping';       // Kuryer qabul qildi, bozorlik qilmoqda
    case Delivering = 'delivering';   // Xarid tugadi, mijozga olib ketilmoqda
    case Delivered = 'delivered';     // Yetkazildi
    case Cancelled = 'cancelled';     // Bekor qilindi

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Yangi',
            self::Confirmed => 'Tasdiqlandi',
            self::Assigned => 'Kuryerga berildi',
            self::Shopping => 'Bozorlik qilinmoqda',
            self::Delivering => 'Yo\'lda',
            self::Delivered => 'Yetkazildi',
            self::Cancelled => 'Bekor qilindi',
        };
    }

    /** Admin paneldagi rang (badge) */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Assigned, self::Shopping, self::Delivering => 'brand',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'ti-clock',
            self::Confirmed => 'ti-circle-check',
            self::Assigned => 'ti-user-check',
            self::Shopping => 'ti-shopping-cart',
            self::Delivering => 'ti-truck-delivery',
            self::Delivered => 'ti-package',
            self::Cancelled => 'ti-circle-x',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Assigned, self::Cancelled],
            self::Assigned => [self::Shopping, self::Confirmed, self::Cancelled],
            self::Shopping => [self::Delivering, self::Cancelled],
            self::Delivering => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /** Kuryer ishlayotgan (band qiladigan) holatlar */
    public function isActiveForCourier(): bool
    {
        return in_array($this, self::courierActive(), true);
    }

    /** Mijoz o'zi bekor qila oladigan holatlar (kuryer bozorlikni boshlamaguncha) */
    public function isCancellableByClient(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Assigned], true);
    }

    /** @return list<self> */
    public static function courierActive(): array
    {
        return [self::Assigned, self::Shopping, self::Delivering];
    }

    /** @return list<self> */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => ! $s->isFinal()));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

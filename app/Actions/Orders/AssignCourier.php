<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderStatusChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTransitioner;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\DB;

/**
 * Moderator kuryerni qo'lda tanlaydi. "Kuryerga berildi" holatida qayta chaqirilsa — kuryer almashtiriladi.
 * Avtomatik taqsimlash qo'shilganda shu amal CourierLocator tanlagan kuryer bilan chaqiriladi.
 */
class AssignCourier
{
    use LocksOrder;

    public function __construct(
        private readonly OrderTransitioner $transitioner,
        private readonly SettingsRepository $settings,
    ) {}

    public function handle(Order $order, User $courier, User $moderator): Order
    {
        return DB::transaction(function () use ($order, $courier, $moderator) {
            $order = $this->lock($order);
            $courier = User::query()->lockForUpdate()->findOrFail($courier->id);

            $this->ensureCourierCanTake($courier, $order);

            if ($order->status === OrderStatus::Assigned) {
                return $this->reassign($order, $courier, $moderator);
            }

            $order->courier_id = $courier->id;
            $this->transitioner->transition($order, OrderStatus::Assigned, $moderator, "Kuryer: {$courier->name}");

            return $order;
        });
    }

    private function reassign(Order $order, User $courier, User $moderator): Order
    {
        if ($order->courier_id === $courier->id) {
            throw new BusinessRuleException('Buyurtma allaqachon shu kuryerga biriktirilgan.');
        }

        $order->courier_id = $courier->id;
        $order->assigned_at = now();
        $order->save();

        $this->transitioner->record($order, OrderStatus::Assigned, OrderStatus::Assigned, $moderator, "Kuryer almashtirildi: {$courier->name}");
        OrderStatusChanged::dispatch($order, OrderStatus::Assigned, OrderStatus::Assigned, $moderator);

        return $order;
    }

    private function ensureCourierCanTake(User $courier, Order $order): void
    {
        if ($courier->role !== UserRole::Courier || ! $courier->is_active) {
            throw new BusinessRuleException('Tanlangan foydalanuvchi faol kuryer emas.');
        }

        $active = $courier->courierOrders()
            ->status(...OrderStatus::courierActive())
            ->whereKeyNot($order->id)
            ->count();

        if ($active >= $this->settings->courierMaxActiveOrders()) {
            throw new BusinessRuleException("{$courier->name} band: {$active} ta faol buyurtmasi bor.");
        }
    }
}

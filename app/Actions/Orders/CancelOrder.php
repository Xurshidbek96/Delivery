<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTransitioner;
use Illuminate\Support\Facades\DB;

/**
 * Bekor qilish. Mijoz — o'z buyurtmasini kuryer bozorlikni boshlaguncha;
 * moderator/admin — yakunlanmagan istalgan buyurtmani, sabab bilan.
 */
class CancelOrder
{
    use LocksOrder;

    public function __construct(private readonly OrderTransitioner $transitioner) {}

    public function handle(Order $order, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $order = $this->lock($order);

            if (! $actor->isStaff()) {
                if ($order->client_id !== $actor->id) {
                    throw new BusinessRuleException('Bu buyurtma sizniki emas.');
                }
                if (! $order->status->isCancellableByClient()) {
                    throw new BusinessRuleException('Kuryer bozorlikni boshlagan — bekor qilish uchun operatorga qo\'ng\'iroq qiling.');
                }
            }

            $order->cancel_reason = $reason;
            $order->cancelled_by_role = $actor->role;

            $this->transitioner->transition($order, OrderStatus::Cancelled, $actor, $reason);

            return $order;
        });
    }
}

<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Orders\OrderTotals;
use Illuminate\Support\Facades\DB;

/** Kuryer bozorlik paytida har bir mahsulotni belgilaydi: olindi (narxi bilan) yoki topilmadi. */
class UpdateOrderItem
{
    use LocksOrder;

    public function __construct(private readonly OrderTotals $totals) {}

    public function handle(Order $order, OrderItem $item, User $courier, OrderItemStatus $status, ?int $price, ?string $courierNote = null): OrderItem
    {
        return DB::transaction(function () use ($order, $item, $courier, $status, $price, $courierNote) {
            $order = $this->lock($order);
            $this->ensureAssignedTo($order, $courier);

            if ($item->order_id !== $order->id) {
                throw new BusinessRuleException('Mahsulot bu buyurtmaga tegishli emas.');
            }
            if ($order->status !== OrderStatus::Shopping) {
                throw new BusinessRuleException('Mahsulotlarni faqat bozorlik paytida belgilash mumkin.');
            }
            if ($status === OrderItemStatus::Purchased && ($price === null || $price <= 0)) {
                throw new BusinessRuleException('Olingan mahsulot narxini kiriting.');
            }

            $item->fill([
                'status' => $status,
                'price' => $status === OrderItemStatus::Purchased ? $price : null,
                'courier_note' => $courierNote,
            ])->save();

            $this->totals->recalculate($order);
            $order->save();

            return $item;
        });
    }
}

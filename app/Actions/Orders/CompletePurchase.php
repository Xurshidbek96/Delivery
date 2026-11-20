<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\LocksOrder;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTotals;
use App\Services\Orders\OrderTransitioner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Kuryer bozorlikni tugatadi (ixtiyoriy chek rasmi bilan) → buyurtma "Yo'lda". */
class CompletePurchase
{
    use LocksOrder;

    public function __construct(
        private readonly OrderTransitioner $transitioner,
        private readonly OrderTotals $totals,
    ) {}

    public function handle(Order $order, User $courier, ?UploadedFile $receipt = null): Order
    {
        return DB::transaction(function () use ($order, $courier, $receipt) {
            $order = $this->lock($order);
            $this->ensureAssignedTo($order, $courier);

            $statuses = $order->items()->pluck('status');

            if ($statuses->contains(OrderItemStatus::Pending)) {
                throw new BusinessRuleException('Barcha mahsulotlarni belgilang: olindi yoki topilmadi.');
            }
            if (! $statuses->contains(OrderItemStatus::Purchased)) {
                throw new BusinessRuleException('Birorta ham mahsulot olinmagan. Moderator bilan bog\'laning.');
            }

            if ($receipt !== null) {
                $order->receipt_path = $receipt->store('receipts/'.now()->format('Y/m'), config('delivery.receipts_disk'));
            }

            $this->totals->recalculate($order);
            $this->transitioner->transition($order, OrderStatus::Delivering, $courier);

            return $order;
        });
    }
}

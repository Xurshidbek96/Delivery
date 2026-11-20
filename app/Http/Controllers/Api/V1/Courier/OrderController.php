<?php

namespace App\Http\Controllers\Api\V1\Courier;

use App\Actions\Orders\AcceptOrder;
use App\Actions\Orders\CompletePurchase;
use App\Actions\Orders\DeclineOrder;
use App\Actions\Orders\DeliverOrder;
use App\Actions\Orders\UpdateOrderItem;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Courier\CompletePurchaseRequest;
use App\Http\Requests\Api\Courier\UpdateItemRequest;
use App\Http\Requests\Api\ReasonRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Kuryer faqat o'ziga biriktirilgan buyurtmalarni ko'radi: har bir so'rov courierOrders() orqali. */
class OrderController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->courierOrders()
            ->withCount('items')
            ->with('client')
            ->when($request->query('scope', 'active') === 'active', fn ($q) => $q->status(...OrderStatus::courierActive()))
            ->when($request->query('scope') === 'history', fn ($q) => $q->status(OrderStatus::Delivered, OrderStatus::Cancelled))
            ->latest('id')
            ->paginate($this->perPage($request));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, int $order): OrderResource
    {
        return $this->detail($this->find($request, $order));
    }

    public function accept(Request $request, int $order, AcceptOrder $action): OrderResource
    {
        return $this->detail($action->handle($this->find($request, $order), $request->user()));
    }

    public function decline(ReasonRequest $request, int $order, DeclineOrder $action): JsonResponse
    {
        $action->handle($this->find($request, $order), $request->user(), $request->validated('reason'));

        return response()->json(['message' => 'Buyurtma moderatorga qaytarildi.']);
    }

    public function updateItem(UpdateItemRequest $request, int $order, int $item, UpdateOrderItem $action): OrderItemResource
    {
        $model = $this->find($request, $order);

        return new OrderItemResource($action->handle(
            $model,
            $model->items()->findOrFail($item),
            $request->user(),
            OrderItemStatus::from($request->validated('status')),
            $request->validated('price'),
            $request->validated('courier_note'),
        ));
    }

    public function completePurchase(CompletePurchaseRequest $request, int $order, CompletePurchase $action): OrderResource
    {
        return $this->detail($action->handle($this->find($request, $order), $request->user(), $request->file('receipt')));
    }

    public function deliver(Request $request, int $order, DeliverOrder $action): OrderResource
    {
        return $this->detail($action->handle($this->find($request, $order), $request->user()));
    }

    private function find(Request $request, int $order): Order
    {
        return $request->user()->courierOrders()->findOrFail($order);
    }

    private function detail(Order $order): OrderResource
    {
        return new OrderResource($order->fresh()->load(['items', 'client', 'histories']));
    }
}

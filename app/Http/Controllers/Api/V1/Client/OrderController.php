<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Client\StoreOrderRequest;
use App\Http\Requests\Api\ReasonRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/** Mijoz faqat o'z buyurtmalarini ko'radi: har bir so'rov clientOrders() orqali. */
class OrderController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->clientOrders()
            ->withCount('items')
            ->with('courier')
            ->when($request->query('scope') === 'active', fn ($q) => $q->status(...OrderStatus::open()))
            ->when($request->query('scope') === 'history', fn ($q) => $q->status(OrderStatus::Delivered, OrderStatus::Cancelled))
            ->latest('id')
            ->paginate($this->perPage($request));

        return OrderResource::collection($orders);
    }

    /**
     * `Idempotency-Key` header'i bilan qayta yuborilgan so'rov yangi buyurtma yaratmaydi
     * (internet uzilib, ilova qayta yuborganda dublikat bo'lmasin).
     */
    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): JsonResponse
    {
        $user = $request->user();
        $key = $request->header('Idempotency-Key');
        $cacheKey = $key ? "idem:order:{$user->id}:".sha1($key) : null;

        if ($cacheKey && ($existingId = Cache::get($cacheKey))) {
            $order = $user->clientOrders()->findOrFail($existingId);

            return $this->detail($order)->response()->setStatusCode(200);
        }

        $order = $placeOrder->handle($user, $request->toData());

        if ($cacheKey) {
            Cache::put($cacheKey, $order->id, now()->addDay());
        }

        return $this->detail($order)->response()->setStatusCode(201);
    }

    public function show(Request $request, int $order): OrderResource
    {
        return $this->detail($request->user()->clientOrders()->findOrFail($order));
    }

    public function cancel(ReasonRequest $request, int $order, CancelOrder $cancelOrder): OrderResource
    {
        $model = $request->user()->clientOrders()->findOrFail($order);

        $cancelOrder->handle($model, $request->user(), $request->validated('reason') ?: 'Mijoz bekor qildi');

        return $this->detail($model);
    }

    private function detail(Order $order): OrderResource
    {
        return new OrderResource($order->fresh()->load(['items', 'courier', 'histories']));
    }
}

<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bitta resurs ikkala ilova uchun: mijoz kuryer kontaktini, kuryer mijoz kontaktini ko'radi.
 * Ichki maydonlar (staff_note, confirmed_by) API'ga chiqmaydi.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isClient = $viewer?->role === UserRole::Client;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'address' => [
                'address' => $this->address,
                'entrance' => $this->entrance,
                'floor' => $this->floor,
                'apartment' => $this->apartment,
                'landmark' => $this->landmark,
                'lat' => $this->lat,
                'lng' => $this->lng,
            ],
            'contact' => ['name' => $this->contact_name, 'phone' => $this->contact_phone],
            'note' => $this->note,
            'items_count' => $this->whenCounted('items'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'amounts' => [
                'items_total' => $this->items_total,
                'delivery_fee' => $this->delivery_fee,
                'service_fee' => $this->service_fee,
                'total' => $this->total,
            ],
            'payment' => [
                'method' => ['value' => $this->payment_method->value, 'label' => $this->payment_method->label()],
                'status' => ['value' => $this->payment_status->value, 'label' => $this->payment_status->label()],
            ],
            'receipt_url' => $this->receiptUrl(),
            'courier' => $this->when($isClient && $this->relationLoaded('courier') && $this->courier, fn () => [
                'name' => $this->courier->name,
                'phone' => $this->courier->phone,
            ]),
            'client' => $this->when(! $isClient && $this->relationLoaded('client'), fn () => [
                'name' => $this->client->name,
                'phone' => $this->client->phone,
            ]),
            'can_cancel' => $this->when($isClient, fn () => $this->status->isCancellableByClient()),
            'cancel_reason' => $this->cancel_reason,
            'history' => OrderStatusHistoryResource::collection($this->whenLoaded('histories')),
            'created_at' => $this->created_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }
}

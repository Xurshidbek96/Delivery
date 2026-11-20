<?php

namespace App\Actions\Orders;

use App\Contracts\DeliveryPricing;
use App\Data\PlaceOrderData;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderTransitioner;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\DB;

/** Mijoz bozorlik ro'yxatini yuboradi → buyurtma "Yangi" holatda moderatorga tushadi. */
class PlaceOrder
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly DeliveryPricing $pricing,
        private readonly PaymentGatewayManager $payments,
        private readonly OrderTransitioner $transitioner,
    ) {}

    public function handle(User $client, PlaceOrderData $data): Order
    {
        if (! $this->settings->acceptingOrders()) {
            throw new BusinessRuleException('Hozir buyurtma qabul qilinmayapti. Birozdan keyin urinib ko\'ring.');
        }

        if (count($data->items) > $this->settings->maxItemsPerOrder()) {
            throw new BusinessRuleException("Bitta buyurtmada ko'pi bilan {$this->settings->maxItemsPerOrder()} ta mahsulot bo'lishi mumkin.");
        }

        $this->payments->for($data->paymentMethod); // usul yoqilganini tekshiradi

        $quote = $this->pricing->quote($data->address['lat'] ?? null, $data->address['lng'] ?? null, count($data->items));

        return DB::transaction(function () use ($client, $data, $quote) {
            $order = Order::query()->create([
                'client_id' => $client->id,
                'status' => OrderStatus::Pending,
                'address' => $data->address['address'],
                'entrance' => $data->address['entrance'] ?? null,
                'floor' => $data->address['floor'] ?? null,
                'apartment' => $data->address['apartment'] ?? null,
                'landmark' => $data->address['landmark'] ?? null,
                'lat' => $data->address['lat'] ?? null,
                'lng' => $data->address['lng'] ?? null,
                'contact_name' => $data->contactName ?: $client->name,
                'contact_phone' => $data->contactPhone ?: $client->phone,
                'note' => $data->note,
                'delivery_fee' => $quote->deliveryFee,
                'service_fee' => $quote->serviceFee,
                'total' => $quote->deliveryFee + $quote->serviceFee,
                'payment_method' => $data->paymentMethod,
                'payment_status' => PaymentStatus::Unpaid,
            ]);

            foreach (array_values($data->items) as $position => $item) {
                $order->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'note' => $item['note'] ?? null,
                    'status' => OrderItemStatus::Pending,
                    'position' => $position,
                ]);
            }

            $this->transitioner->record($order, null, OrderStatus::Pending, $client);

            return $order->load('items');
        });
    }
}

<?php

namespace Database\Seeders;

use App\Actions\Orders\AcceptOrder;
use App\Actions\Orders\AssignCourier;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\CompletePurchase;
use App\Actions\Orders\ConfirmOrder;
use App\Actions\Orders\DeliverOrder;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\UpdateOrderItem;
use App\Data\PlaceOrderData;
use App\Enums\CourierVehicle;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Test rejimi uchun demo: moderator, 3 kuryer, 3 mijoz va turli holatdagi buyurtmalar.
 * Buyurtmalar haqiqiy Action'lar orqali o'tkaziladi — tarix va summalar to'g'ri bo'ladi.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->exists()) {
            $this->command?->warn('Buyurtmalar bor — demo ma\'lumot qo\'shilmadi.');

            return;
        }

        $moderator = User::query()->firstOrCreate(['phone' => '+998900000002'], [
            'name' => 'Dilnoza Moderator', 'role' => UserRole::Moderator, 'password' => 'moderator123',
        ]);

        $couriers = collect([
            ['+998901110001', 'Jasur Karimov', CourierVehicle::Scooter, 41.3111, 69.2797],
            ['+998901110002', 'Sardor Aliyev', CourierVehicle::Car, 41.3260, 69.2280],
            ['+998901110003', 'Bekzod Rahimov', CourierVehicle::Bicycle, null, null],
        ])->map(function (array $c) {
            $user = User::query()->create(['phone' => $c[0], 'name' => $c[1], 'role' => UserRole::Courier, 'password' => 'courier123']);
            $user->courierProfile()->create([
                'vehicle' => $c[2], 'is_online' => $c[3] !== null,
                'last_lat' => $c[3], 'last_lng' => $c[4], 'last_location_at' => $c[3] ? now() : null,
            ]);

            return $user;
        });

        $clients = collect([
            ['+998935550001', 'Malika Yusupova'],
            ['+998935550002', 'Aziz Toshmatov'],
            ['+998935550003', 'Nodira Qodirova'],
        ])->map(fn (array $c) => User::query()->create(['phone' => $c[0], 'name' => $c[1], 'role' => UserRole::Client]));

        $clients[0]->addresses()->create(['title' => 'Uy', 'address' => 'Chilonzor 9-kvartal, 12-uy', 'entrance' => '2', 'floor' => '4', 'apartment' => '38', 'lat' => 41.2856, 'lng' => 69.2034]);

        $lists = [
            [['Kartoshka', 3, 'kg'], ['Piyoz', 1, 'kg'], ['Sabzi', 2, 'kg'], ['Non (tandir)', 4, 'pcs', 'issiq bo\'lsin']],
            [['Sut 3.2%', 2, 'l'], ['Tuxum', 10, 'pcs'], ['Qatiq', 1, 'pcs'], ['Pishloq', 300, 'g']],
            [['Pomidor', 1.5, 'kg'], ['Bodring', 1, 'kg'], ['Ko\'k piyoz', 2, 'bunch'], ['Limon', 3, 'pcs']],
            [['Guruch (lazer)', 2, 'kg'], ['Mol go\'shti', 1, 'kg', 'yog\'siz'], ['Sabzi (sariq)', 2, 'kg'], ['Zira', 1, 'pack']],
            [['Olma', 2, 'kg'], ['Banan', 1, 'kg'], ['Apelsin', 2, 'kg']],
            [['Choy (ko\'k)', 1, 'pack'], ['Shakar', 1, 'kg'], ['Pechenye', 2, 'pack']],
        ];
        $addresses = [
            ['Yunusobod 4-kvartal, 15-uy', 41.3640, 69.2870, 'Makro yonida'],
            ['Mirzo Ulug\'bek, Buyuk Ipak yo\'li 77', 41.3260, 69.3350, 'Metro chiqishi'],
            ['Shayxontohur, Navoiy ko\'chasi 30', 41.3190, 69.2510, null],
            ['Sergeli 6-mavze, 41-uy', 41.2250, 69.2190, 'Maktab ro\'parasi'],
            ['Chilonzor 9-kvartal, 12-uy', 41.2856, 69.2034, null],
            ['Olmazor, Qorasaroy 5', 41.3510, 69.2180, '"Korzinka" orqasida'],
        ];

        $place = function (int $i) use ($clients, $lists, $addresses): Order {
            [$addr, $lat, $lng, $landmark] = $addresses[$i];

            return app(PlaceOrder::class)->handle($clients[$i % 3], new PlaceOrderData(
                address: ['address' => $addr, 'lat' => $lat, 'lng' => $lng, 'landmark' => $landmark, 'floor' => '3', 'apartment' => (string) (10 + $i)],
                items: array_map(fn ($it) => ['name' => $it[0], 'quantity' => $it[1], 'unit' => $it[2], 'note' => $it[3] ?? null], $lists[$i]),
                paymentMethod: PaymentMethod::Cash,
                note: $i === 0 ? 'Domofon ishlamaydi, qo\'ng\'iroq qiling' : null,
            ));
        };

        $buyAll = function (Order $order, User $courier) {
            foreach ($order->items as $item) {
                app(UpdateOrderItem::class)->handle($order, $item, $courier, OrderItemStatus::Purchased, random_int(5, 60) * 1000);
            }
        };

        // 1) Yetkazilgan
        $o = $place(4);
        app(ConfirmOrder::class)->handle($o, $moderator);
        app(AssignCourier::class)->handle($o, $couriers[0], $moderator);
        app(AcceptOrder::class)->handle($o, $couriers[0]);
        $buyAll($o->fresh('items'), $couriers[0]);
        app(CompletePurchase::class)->handle($o, $couriers[0]);
        app(DeliverOrder::class)->handle($o, $couriers[0]);

        // 2) Yo'lda
        $o = $place(3);
        app(ConfirmOrder::class)->handle($o, $moderator);
        app(AssignCourier::class)->handle($o, $couriers[1], $moderator);
        app(AcceptOrder::class)->handle($o, $couriers[1]);
        $buyAll($o->fresh('items'), $couriers[1]);
        app(CompletePurchase::class)->handle($o, $couriers[1]);

        // 3) Bozorlikda
        $o = $place(2);
        app(ConfirmOrder::class)->handle($o, $moderator);
        app(AssignCourier::class)->handle($o, $couriers[0], $moderator);
        app(AcceptOrder::class)->handle($o, $couriers[0]);

        // 4) Tasdiqlangan — kuryer tanlash kutilmoqda
        $o = $place(1);
        app(ConfirmOrder::class)->handle($o, $moderator);

        // 5) Bekor qilingan
        $o = $place(5);
        app(CancelOrder::class)->handle($o, $clients[2], 'Rejalar o\'zgardi');

        // 6) Yangi — moderator tasdig'ini kutmoqda
        $place(0);

        $this->command?->info('Demo tayyor. Admin: +998900000001 / admin12345, moderator: +998900000002 / moderator123, kuryer: +998901110001 / courier123, mijoz OTP: '.(config('delivery.otp.fixed_code') ?? 'SMS (log)'));
    }
}

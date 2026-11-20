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
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Test rejimi uchun demo: moderator, 3 kuryer, 3 mijoz, katalog (Namangan do'konlari) va turli holatdagi buyurtmalar.
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
            ['+998901110001', 'Jasur Karimov', CourierVehicle::Scooter, 40.9983, 71.6726],
            ['+998901110002', 'Sardor Aliyev', CourierVehicle::Car, 41.0060, 71.6550],
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

        $clients[0]->addresses()->create(['title' => 'Uy', 'address' => 'Navoiy ko\'chasi, 12-uy', 'entrance' => '2', 'floor' => '4', 'apartment' => '38', 'lat' => 41.0012, 'lng' => 71.6698]);

        $this->seedCatalog();

        $lists = [
            [['Kartoshka', 3, 'kg'], ['Piyoz', 1, 'kg'], ['Sabzi', 2, 'kg'], ['Non (tandir)', 4, 'pcs', 'issiq bo\'lsin']],
            [['Sut 3.2%', 2, 'l'], ['Tuxum', 10, 'pcs'], ['Qatiq', 1, 'pcs'], ['Pishloq', 300, 'g']],
            [['Pomidor', 1.5, 'kg'], ['Bodring', 1, 'kg'], ['Ko\'k piyoz', 2, 'bunch'], ['Limon', 3, 'pcs']],
            [['Guruch (lazer)', 2, 'kg'], ['Mol go\'shti', 1, 'kg', 'yog\'siz'], ['Sabzi (sariq)', 2, 'kg'], ['Zira', 1, 'pack']],
            [['Olma', 2, 'kg'], ['Banan', 1, 'kg'], ['Apelsin', 2, 'kg']],
            [['Choy (ko\'k)', 1, 'pack'], ['Shakar', 1, 'kg'], ['Pechenye', 2, 'pack']],
        ];
        $addresses = [
            ['Uychi ko\'chasi, 15-uy', 41.0050, 71.6600, 'Chorsu bozori yonida'],
            ['Bobur shoh ko\'chasi 88', 40.9920, 71.6850, 'Maktab ro\'parasi'],
            ['Mashrab ko\'chasi 30', 40.9950, 71.6650, null],
            ['Davlatobod, 5-mavze, 17-uy', 41.0180, 71.6450, 'Masjid ro\'parasi'],
            ['Kosonsoy ko\'chasi 7', 41.0100, 71.6800, null],
            ['Lola ko\'chasi 5', 40.9870, 71.6600, '"Korzinka" orqasida'],
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

    /** Katalog: 3 faol do'kon va 1 nofaol (mijozga ko'rinmasligini ko'rsatish uchun). Narx — taxminiy, 1 birlik uchun. */
    private function seedCatalog(): void
    {
        $shops = [
            ['Chorsu bozori', 'Navoiy ko\'chasi, sabzavot rastasi', '+998692001122', 40.9995, 71.6712, true, [
                ['Kartoshka', 'kg', 7000], ['Piyoz', 'kg', 5000], ['Sabzi (sariq)', 'kg', 6000], ['Pomidor', 'kg', 18000],
                ['Bodring', 'kg', 12000], ['Ko\'k piyoz', 'bunch', 2000], ['Olma', 'kg', 15000], ['Mol go\'shti', 'kg', 110000],
            ]],
            ['Tandir non uyi', 'Uychi ko\'chasi 3', '+998692003344', 41.0042, 71.6611, true, [
                ['Non (tandir)', 'pcs', 4000], ['Patir', 'pcs', 8000], ['Somsa', 'pcs', 7000, false],
            ]],
            ['Sut va sut mahsulotlari', 'Bobur shoh ko\'chasi 12', null, 40.9931, 71.6833, true, [
                ['Sut 3.2%', 'l', 12000], ['Qatiq', 'pcs', 9000], ['Tuxum', 'pcs', 1500], ['Pishloq', 'g', null],
            ]],
            ['Yopilgan do\'kon', 'Lola ko\'chasi 5', null, null, null, false, [
                ['Shakar', 'kg', 14000],
            ]],
        ];

        foreach ($shops as $order => [$name, $address, $phone, $lat, $lng, $active, $products]) {
            $shop = Shop::query()->create([
                'name' => $name, 'address' => $address, 'phone' => $phone, 'lat' => $lat, 'lng' => $lng,
                'is_active' => $active, 'sort_order' => $order,
            ]);
            foreach ($products as $i => $p) {
                $shop->products()->create(['name' => $p[0], 'unit' => $p[1], 'price' => $p[2], 'is_active' => $p[3] ?? true, 'sort_order' => $i]);
            }
        }
    }
}

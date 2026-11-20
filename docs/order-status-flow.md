# Buyurtma holatlari

Manba: `app/Enums/OrderStatus.php` (graf) va `app/Actions/Orders/*` (kim bajaradi).
Holatni faqat `OrderTransitioner` o'zgartiradi — u grafni tekshiradi, vaqt belgisini qo'yadi, `order_status_histories` ga yozadi va `OrderStatusChanged` eventini chiqaradi.

```
                 ┌──────────── cancelled ◄───────────┐ (moderator/admin: istalgan ochiq holatdan)
                 │                                    │
 pending ──► confirmed ──► assigned ──► shopping ──► delivering ──► delivered
 (mijoz)    (moderator)   (moderator)   (kuryer)      (kuryer)       (kuryer)
                 ▲            │
                 └────────────┘ kuryer rad etdi (decline)
```

| O'tish | Amal (Action) | Kim | Shartlar |
|---|---|---|---|
| — → `pending` | `PlaceOrder` | mijoz | `accepting_orders` yoqilgan, mahsulotlar ≤ `max_items_per_order`, to'lov usuli yoqilgan |
| `pending` → `confirmed` | `ConfirmOrder` | moderator/admin | yetkazish narxini o'zgartirishi mumkin |
| `confirmed` → `assigned` | `AssignCourier` | moderator/admin | kuryer faol, faol buyurtmalari < `courier_max_active_orders` |
| `assigned` → `assigned` | `AssignCourier` | moderator/admin | kuryerni almashtirish (tarixga yoziladi) |
| `assigned` → `confirmed` | `DeclineOrder` | biriktirilgan kuryer | `courier_id` tozalanadi, moderator boshqasini tanlaydi |
| `assigned` → `shopping` | `AcceptOrder` | biriktirilgan kuryer | |
| (`shopping` ichida) | `UpdateOrderItem` | biriktirilgan kuryer | har mahsulot: `purchased` (narx bilan) yoki `unavailable` |
| `shopping` → `delivering` | `CompletePurchase` | biriktirilgan kuryer | hamma mahsulot belgilangan, kamida bittasi olingan; chek rasmi ixtiyoriy |
| `delivering` → `delivered` | `DeliverOrder` | biriktirilgan kuryer | to'lov drayveri `onDelivered()` (naqd → `paid`) |
| ochiq → `cancelled` | `CancelOrder` | mijoz: `pending/confirmed/assigned`; xodim: istalgan ochiq | sabab majburiy (xodim) |

## Pul

- `items_total` — kuryer kiritgan `purchased` mahsulot narxlari yig'indisi (`OrderTotals::recalculate`).
- `total = items_total + delivery_fee + service_fee`. Mijoz yuborgan narx maydonlari e'tiborsiz.
- `delivery_fee`/`service_fee` buyurtma yaratilganda `DeliveryPricing` dan olinadi (hozir: sozlamadagi qat'iy narx).

## Bildirishnomalar

`SendOrderStatusNotifications` listeneri (`OrderStatusNotification`, kanallar: `database` + `PushChannel`):
- mijozga: confirmed, shopping, delivering, delivered, cancelled;
- kuryerga: yangi biriktirish, faol buyurtmasi bekor qilinganda.
Amalni bajargan odamning o'ziga xabar ketmaydi.

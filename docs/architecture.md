# Arxitektura

Laravel 12 monolit: mobil ilovalar uchun REST API (`/api/v1`, Sanctum token) va xodimlar uchun Blade admin panel (`/admin`, sessiya). Biznes mantiq bitta joyda — ikkala kirish nuqtasi bir xil Action'larni chaqiradi.

```
 Mijoz ilovasi ─┐                         ┌─► Actions/Orders/*  ──► OrderTransitioner ──► OrderStatusChanged
 Kuryer ilovasi ─┼─► Api/V1 controllers ──┤        │                      │                     │
 Admin (web) ────┴─► Admin controllers ───┘        ▼                      ▼                     ▼
                                               Services/*            order_status_histories   Listeners → Notifications
                                               (Settings, Pricing,                              (database + push)
                                                Payments, Couriers)
```

## Qatlamlar

| Qatlam | Papka | Vazifa |
|---|---|---|
| Domen lug'ati | `app/Enums` | Holatlar, rollar, birliklar, to'lov usullari — label/rang bilan |
| Use-case | `app/Actions/Orders` | Har bir biznes amal — bitta klass, `handle()`, tranzaksiya + `lockForUpdate` |
| Servislar | `app/Services` | `OrderTransitioner`, `OrderTotals`, `CourierLocator`, `SettingsRepository`, auth/OTP |
| Kontraktlar | `app/Contracts` | Almashtiriladigan drayverlar (pastga qarang) |
| HTTP | `app/Http` | Yupqa controllerlar, FormRequest (validatsiya + DTO), API Resource |
| Ko'rinish | `resources/views/admin`, `public/css/admin.css`, `public/js/admin.js` | Build qadamisiz admin panel |
| Katalog | `Models\Shop`, `Models\Product` (`scopeVisible`), `Models\Concerns\HasImage` | Do'kon → mahsulotlar. Mijozga ko'rinish qoidasi faqat `Product::visible()` / `Shop::active()` da. Rasmlar `delivery.catalog_disk` |

## Kengaytirish nuqtalari

| Nima | Kontrakt | Hozir | Keyin |
|---|---|---|---|
| To'lov | `PaymentGateway` + `PaymentGatewayManager` | `CashPaymentGateway` | Click/Payme: enum'da `isEnabled()`, drayver, webhook controller |
| Yetkazish narxi | `DeliveryPricing` | `FlatDeliveryPricing` (sozlama) | masofa/zona bo'yicha; `AppServiceProvider` da bind |
| Kuryer tanlash | `CourierLocator::candidatesFor()` | moderator ro'yxatdan qo'lda tanlaydi | avtomatik: `candidatesFor($order)->firstWhere('has_capacity')` → `AssignCourier` (masalan `OrderStatusChanged(confirmed)` listenerida) |
| SMS | `SmsSender` | `LogSmsSender` | Eskiz drayveri, `SMS_DRIVER=eskiz` |
| Push | `PushNotifier` | `LogPushNotifier` | FCM (`device_tokens` jadvali tayyor), notification'ga `ShouldQueue` |
| Real vaqt | `OrderStatusChanged` event | admin 15 s polling | Reverb/WebSocket broadcast |

## Xavfsizlik qoidalari

- **Klientga ishonilmaydi:** narx, rol, egalik faqat serverda. Mijoz yuborgan `price/total/delivery_fee` e'tiborsiz.
- **Egalik so'rovda:** mijoz — `$user->clientOrders()->findOrFail()`, kuryer — `$user->courierOrders()->findOrFail()`; boshqasiniki 404. Saqlangan manzil — `Rule::exists(...)->where('user_id', ...)`.
- **Rollar:** `role:client|courier|moderator,admin` middleware; admin URL'larida `Route::bind('courier'|'client'|'staff')` rolga qarab qidiradi.
- **Fayl yuklash:** faqat `file|image|mimes:jpg,jpeg,png,webp|max:5120`.
- **Parallel so'rovlar:** har bir Action buyurtmani `lockForUpdate` bilan qayta o'qiydi.
- **Idempotentlik:** `POST /client/orders` — `Idempotency-Key` header'i, kesh kaliti foydalanuvchiga bog'langan.
- **OTP:** hash'langan, 5 urinish, 60 s qayta yuborish, rate limit (telefon + IP). `OTP_FIXED_CODE` faqat test rejimi uchun — productionda bo'sh.
- `env()` faqat `config/*` da.

---
name: order-flow
description: Delivery buyurtma oqimini o'zgartirish — holatlar va o'tishlar, Actions (tasdiqlash, kuryer tanlash, qabul/rad, mahsulot narxi, bozorlikni tugatish, yetkazish, bekor qilish), summalar, yetkazish narxi, kuryer limiti, bildirishnomalar, to'lov drayverlari (Click/Payme), avtomatik kuryer taqsimlash. Ishlating, agar foydalanuvchi "status", "holat", "buyurtma", "bekor qilish", "kuryer tanlash", "eng yaqin kuryer", "avtomatik", "narx", "yetkazish narxi", "summa", "to'lov", "Click", "Payme", "bildirishnoma", "push" desa.
---

# Buyurtma oqimi

Avval o'qing: `docs/order-status-flow.md` (graf va kim nima qiladi), `TZ.md` §6, §8.

## Qoidalar

- **Holat faqat `OrderTransitioner::transition()` orqali.** U grafni (`OrderStatus::allowedTransitions()`) tekshiradi, `*_at` vaqtini qo'yadi, `order_status_histories` ga yozadi, `OrderStatusChanged` (after commit) chiqaradi. Holat o'zgarmaydigan, lekin tarixda kerak voqea — `record()`.
- **Har bir amal — `Actions\Orders\*`:** `DB::transaction` → `$this->lock($order)` → tekshiruvlar → transition. Kuryer amali — `ensureAssignedTo()`. Aktyor (`User`) parametr sifatida keladi, controller egalikni allaqachon scoped so'rov bilan tekshirgan bo'lsa ham Action o'zi ham tekshiradi.
- **Summa** faqat `OrderTotals::recalculate()`; mijoz narx yubormaydi.
- Yangi holat: `OrderStatus` (case, `label`, `tone`, `icon`, `allowedTransitions`, kerak bo'lsa `courierActive()`/`isCancellableByClient()`), `OrderTransitioner::TIMESTAMPS` + migratsiya, `OrderStatusNotification::forClient/forCourier`, admin `OrderController::TABS`, `orders/show.blade.php` `$flow`, `OrderStatusTest`, docs.
- Sozlama (limit, narx) — `config/delivery.php` `defaults` + `SettingsRequest` + `settings/edit.blade.php` + `SettingsRepository` getter.

## Kengaytirish retseptlari

- **Avtomatik kuryer:** `OrderStatusChanged` listener — `to === Confirmed` (va decline'dan keyin `from === Assigned`) bo'lsa `CourierLocator::candidatesFor($order)->first(fn ($c) => $c->has_capacity && $c->courierProfile->is_online)` → `AssignCourier::handle($order, $courier, $systemActor)`. Topilmasa — moderator qo'lda. Rad etgan kuryerni qayta tanlamaslik uchun tarixdan (`comment` emas, alohida jadval yoki `declined_by`) foydalaning. Sozlama bilan yoqiladigan qiling.
- **Masofa bo'yicha narx:** `DeliveryPricing` implementatsiyasi (`Services\Pricing\DistancePricing`), `AppServiceProvider` bind, sozlamalar (baza narx, km narxi).
- **Onlayn to'lov:** `PaymentMethod` `isEnabled()`, `PaymentGateway` drayveri (`checkoutUrl`, `onDelivered`), `PaymentGatewayManager::$gateways`, webhook controller (imzo tekshiruvi, idempotent — tranzaksiya ID bo'yicha), `payments` jadvali. Validatsiya `PaymentMethod::enabled()` dan avtomatik kengayadi.
- **Push:** `PushNotifier` FCM drayveri (`device_tokens`), `config/delivery.php` `push.driver`, `OrderStatusNotification implements ShouldQueue` + navbat ishchisi.

## Test

`tests/Feature/Api/CourierOrderTest`, `ClientOrderTest`, `Admin/AdminPanelTest` naqshlari: to'g'ri yo'l + noto'g'ri holatdan urinish (422) + begona aktyor. Tarix: `$order->histories()->pluck('to_status')`.

Tugatgach — `docs` va `verify` skill'lari.

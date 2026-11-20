# Delivery REST API v1

Baza URL: `https://<domen>/api/v1`. Barcha so'rovlarda `Accept: application/json`.
Avtorizatsiya: `Authorization: Bearer <token>`. Javob va xato xabarlari o'zbekcha.

**Xatolar:** `401` — token yo'q/yaroqsiz, `403` — rol mos emas yoki akkaunt bloklangan, `404` — topilmadi (boshqa foydalanuvchining resursi ham 404), `422` — validatsiya (`errors` bilan) yoki biznes qoida (`message`), `429` — juda ko'p so'rov.

**Sahifalash:** ro'yxatlarda `?limit=1..100` (standart 20), `?page=N`; javobda `data`, `links`, `meta`.

**Lug'at maydonlari** `{ "value": "...", "label": "..." }` ko'rinishida — ilova `value` bo'yicha ishlaydi, `label` ni ko'rsatadi.

---

## Umumiy

### `GET /meta` (avtorizatsiyasiz)
Ilova ochilganda: sozlamalar va lug'atlar.
```json
{
  "accepting_orders": true,
  "delivery_fee": 15000,
  "service_fee": 0,
  "max_items_per_order": 50,
  "support_phone": "+998 90 000 00 00",
  "units": [{"value": "pcs", "label": "dona"}, {"value": "kg", "label": "kg"}, ...],
  "payment_methods": [{"value": "cash", "label": "Naqd (kuryerga)"}],
  "order_statuses": [{"value": "pending", "label": "Yangi"}, ...]
}
```

### `GET /me` · `PATCH /me` `{name}` · `POST /auth/logout`
### `GET /notifications` → `data[]` (`id, title, body, order_id, read, created_at`) + `unread_count`
### `POST /notifications/{id}/read` · `POST /notifications/read-all`
### `POST /devices` `{token, platform: android|ios}` · `DELETE /devices` `{token}` — FCM tokeni

---

## Mijoz ilovasi

### Kirish (SMS kod)
1. `POST /auth/otp/send` `{ "phone": "+998901234567" }` → `{ "message", "expires_in": 300 }`. Qayta yuborish — 60 soniyadan keyin.
2. `POST /auth/otp/verify` `{ phone, code, name?, device_name? }` → `{ token, is_new, user }`.
   `is_new: true` bo'lsa ilova ism so'rashi mumkin (`PATCH /me`).

Telefon istalgan ko'rinishda (`90 123 45 67`, `+998 (90) ...`) — server `+998XXXXXXXXX` ga keltiradi.

### Manzillar
- `GET /client/addresses`
- `POST /client/addresses` `{ title?, address, entrance?, floor?, apartment?, landmark?, lat?, lng? }`
- `PUT /client/addresses/{id}` · `DELETE /client/addresses/{id}`

### Buyurtmalar
`POST /client/orders` — header `Idempotency-Key: <uuid>` tavsiya etiladi (qayta yuborilsa dublikat yaratilmaydi, `200` bilan o'sha buyurtma qaytadi).
```json
{
  "address_id": 5,                       // yoki quyidagi maydonlar:
  "address": "Yunusobod 4-kvartal, 15-uy",
  "entrance": "2", "floor": "4", "apartment": "38",
  "landmark": "Makro yonida",
  "lat": 41.364, "lng": 69.287,
  "items": [
    { "name": "Kartoshka", "quantity": 3, "unit": "kg" },
    { "name": "Non", "quantity": 4, "unit": "pcs", "note": "issiq bo'lsin" }
  ],
  "payment_method": "cash",
  "contact_name": "Malika",              // ixtiyoriy, standart — profil ismi
  "contact_phone": "+998935550001",      // ixtiyoriy, standart — profil telefoni
  "note": "Domofon ishlamaydi"
}
```
Narx yuborilmaydi: yetkazish narxi serverda, mahsulot narxlarini kuryer xariddan keyin kiritadi. Javob — `201` va buyurtma (pastga qarang).

- `GET /client/orders?scope=active|history`
- `GET /client/orders/{id}`
- `POST /client/orders/{id}/cancel` `{ reason? }` — faqat kuryer bozorlikni boshlaguncha (`can_cancel: true`).

### Buyurtma obyekti
```json
{
  "id": 7, "number": "D000007",
  "status": {"value": "delivering", "label": "Yo'lda"},
  "address": {"address": "...", "entrance": null, "floor": "3", "apartment": "12", "landmark": null, "lat": 41.3, "lng": 69.2},
  "contact": {"name": "Malika", "phone": "+998935550001"},
  "note": null,
  "items": [{"id": 1, "name": "Kartoshka", "quantity": 3, "unit": {"value": "kg", "label": "kg"}, "note": null,
             "status": {"value": "purchased", "label": "Olindi"}, "price": 24000, "courier_note": null}],
  "amounts": {"items_total": 24000, "delivery_fee": 15000, "service_fee": 0, "total": 39000},
  "payment": {"method": {"value": "cash", ...}, "status": {"value": "unpaid", ...}},
  "receipt_url": "https://.../storage/receipts/2026/11/x.jpg",
  "courier": {"name": "Jasur", "phone": "+998901110001"},   // faqat mijozga
  "client":  {"name": "Malika", "phone": "+998935550001"},  // faqat kuryerga
  "can_cancel": false,                                      // faqat mijozga
  "cancel_reason": null,
  "history": [{"status": {...}, "comment": null, "created_at": "..."}],
  "created_at": "...", "confirmed_at": "...", "assigned_at": "...", "delivered_at": null, "cancelled_at": null
}
```
Ro'yxatlarda `items`/`history` o'rniga `items_count` keladi.

---

## Kuryer ilovasi

### Kirish
`POST /auth/courier/login` `{ phone, password, device_name? }` → `{ token, user }` (`user.courier`: transport, `is_online`).
Login va parolni admin paneldan administrator beradi.

### Holat va joylashuv
- `PATCH /courier/online` `{ is_online: true|false }` — ishga chiqdim / dam olyapman
- `POST /courier/location` `{ lat, lng }` — ilova ochiq paytda har 30–60 soniyada

### Buyurtmalar
- `GET /courier/orders?scope=active|history` (standart `active`)
- `GET /courier/orders/{id}`
- `POST /courier/orders/{id}/accept` — `assigned → shopping`
- `POST /courier/orders/{id}/decline` `{ reason? }` — buyurtma moderatorga qaytadi
- `PATCH /courier/orders/{id}/items/{itemId}` `{ status: purchased|unavailable, price?, courier_note? }`
  `purchased` uchun `price` (qator summasi, so'm) majburiy. Faqat `shopping` holatida.
- `POST /courier/orders/{id}/purchase-complete` (multipart, `receipt` — ixtiyoriy rasm, ≤5 MB) — `shopping → delivering`.
  Hamma mahsulot belgilangan va kamida bittasi olingan bo'lishi kerak.
- `POST /courier/orders/{id}/deliver` — `delivering → delivered`, naqd to'lov "to'langan" bo'ladi.

Kuryer faqat o'ziga biriktirilgan buyurtmalarni ko'radi; boshqasiniki — `404`.

---

## Test rejimi

- SMS yuborilmaydi: kod `storage/logs/laravel.log` ga yoziladi; `OTP_FIXED_CODE=1111` bo'lsa kod doim `1111`.
- Demo akkauntlar `php artisan db:seed` dan keyin: kuryer `+998901110001` / `courier123`; mijozlar `+99893555000{1..3}`.

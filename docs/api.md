# Delivery API v1 — mobil dasturchilar uchun qo'llanma

Mijoz va kuryer ilovalari uchun REST API. Bu hujjat — **qanday ishlatish** (oqimlar, qoidalar, UI tavsiyalari).
Har bir maydonning aniq sxemasi va "Try it out" — **Swagger**: `https://<server>/docs/api` (manba: [openapi.yaml](openapi.yaml)).
Postman: [postman/](postman/) papkasi (6-bo'lim).

| | |
|---|---|
| Baza URL | `https://<server>/api/v1` (lokal: `http://127.0.0.1:8000/api/v1`) |
| Format | JSON, UTF-8. Har so'rovda `Accept: application/json` |
| Avtorizatsiya | `Authorization: Bearer <token>` (Laravel Sanctum, muddatsiz — chiqishgacha) |
| Vaqt | ISO 8601, `+05:00` (Toshkent) |
| Pul | Butun son, so'm (`15000` = 15 000 so'm) |
| Telefon | Javobda doim `+998XXXXXXXXX`; so'rovda istalgan ko'rinishda (`90 123 45 67` ham bo'ladi) |
| Til | Barcha `message` va `label` o'zbekcha — foydalanuvchiga to'g'ridan-to'g'ri ko'rsatish mumkin |

---

## 1. Umumiy qoidalar

### 1.1. Javob formati
```jsonc
// Bitta obyekt
{ "data": { ... } }

// Ro'yxat (sahifalangan)
{ "data": [ ... ], "links": { "next": "...", ... }, "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 } }

// Oddiy amal (logout, delete, decline ...)
{ "message": "Saqlandi." }
```
Istisno: `/auth/*` va `/meta` javoblari `data` ichiga o'ralmagan.

### 1.2. Lug'at maydonlari
Holat, birlik, to'lov usuli va h.k. — `{ "value": "shopping", "label": "Bozorlik qilinmoqda" }`.
**Mantiqni faqat `value` bo'yicha quring**, foydalanuvchiga `label` ni ko'rsating (matn serverda o'zgarishi mumkin).

### 1.3. Sahifalash
`?page=2&limit=20`. `limit` 1..100, standart 20. Keyingi sahifa bormi — `links.next !== null`.

### 1.4. Xatolar

| HTTP | Qachon | Ilova nima qiladi |
|---|---|---|
| `401` | Token yo'q, noto'g'ri yoki bekor qilingan (logout, parol almashgan, bloklangan) | Tokenni o'chiring → login ekrani |
| `403` | Rol mos emas (mijoz tokeni bilan kuryer API) yoki akkaunt bloklangan | `message` ni ko'rsating; bloklangan bo'lsa — qo'llab-quvvatlash telefoni (`/meta`) |
| `404` | Topilmadi **yoki boshqa foydalanuvchiga tegishli** | "Topilmadi", ro'yxatga qaytish |
| `422` + `errors` | Validatsiya | `errors.<maydon>[0]` ni maydon ostida ko'rsating |
| `422` faqat `message` | Biznes qoida (masalan, buyurtma allaqachon bekor qilingan, kod noto'g'ri) | `message` ni toast/dialogda ko'rsating, ekranni yangilang (`GET` qaytadan) |
| `429` | Juda ko'p so'rov | `Retry-After` soniyadan keyin |
| `5xx` | Server xatosi | "Xatolik, qayta urinib ko'ring" |

```json
{
  "message": "Manzilni kiriting yoki saqlangan manzilni tanlang. (yana 1 ta xato)",
  "errors": {
    "address": ["Manzilni kiriting yoki saqlangan manzilni tanlang."],
    "items": ["Kamida bitta mahsulot qo'shing."]
  }
}
```
Massiv elementlari xatosi: `items.0.unit`, `items.2.name`.

### 1.5. Cheklovlar (rate limit)
OTP yuborish — raqamga 3/daqiqa (va 60 soniyada bir marta), IP ga 10/daqiqa. Login/verify — 10/daqiqa. Qolgan API — foydalanuvchiga 120/daqiqa.

---

## 2. Mijoz ilovasi

### 2.1. Ekran → endpoint

| Ekran | Endpoint |
|---|---|
| Splash | `GET /meta` (+ token bo'lsa `GET /me`) |
| Telefon kiritish | `POST /auth/otp/send` |
| Kod kiritish | `POST /auth/otp/verify` → `is_new` bo'lsa ism ekrani → `PATCH /me` |
| Ro'yxat tuzish | — (lokal; birliklar `meta.units`, chegarasi `meta.max_items_per_order`) |
| Manzil tanlash | `GET /client/addresses`, `POST /client/addresses` |
| Tasdiqlash | `POST /client/orders` (yetkazish narxi `meta.delivery_fee` dan ko'rsatiladi) |
| Buyurtmalarim | `GET /client/orders?scope=active` / `?scope=history` |
| Buyurtma | `GET /client/orders/{id}`, `POST /client/orders/{id}/cancel` |
| Bildirishnomalar | `GET /notifications`, `POST /notifications/read-all` |
| Profil | `GET/PATCH /me`, `POST /auth/logout` |

### 2.2. Ilova ishga tushishi — `GET /meta`
```json
{
  "accepting_orders": true,
  "delivery_fee": 15000,
  "service_fee": 0,
  "max_items_per_order": 50,
  "support_phone": "+998 90 000 00 00",
  "units": [{"value":"pcs","label":"dona"},{"value":"kg","label":"kg"},{"value":"g","label":"gramm"},
            {"value":"l","label":"litr"},{"value":"pack","label":"qadoq"},{"value":"bunch","label":"bog'"}],
  "payment_methods": [{"value":"cash","label":"Naqd (kuryerga)"}],
  "order_statuses": [{"value":"pending","label":"Yangi"}, "..."]
}
```
`accepting_orders: false` → "Hozir buyurtma qabul qilinmayapti" bannerini ko'rsating, buyurtma tugmasini o'chiring.

### 2.3. Kirish (SMS kod)
```http
POST /auth/otp/send
{ "phone": "+998935550001" }
→ 200 { "message": "Kod yuborildi.", "expires_in": 300 }
```
"Qayta yuborish" tugmasini 60 soniyadan keyin faollashtiring (oldinroq bosilsa — `422 "Kodni qayta yuborish uchun N soniya kuting."`).

```http
POST /auth/otp/verify
{ "phone": "+998935550001", "code": "1111", "name": "Malika", "device_name": "iPhone 15" }
→ 200
{
  "token": "1|lGK5pOVMyATPd2G7fIDXhI5Z08JlfktcBYeThMR5b0cfe63a",
  "is_new": false,
  "user": { "id": 6, "name": "Malika Yusupova", "phone": "+998935550001", "role": "client", "created_at": "2026-11-20T13:56:58+05:00" }
}
```
- Tokenni xavfsiz joyda saqlang (Keychain / EncryptedSharedPreferences).
- `422 "Kod noto'g'ri yoki muddati o'tgan."` — kod xato; 5 marta xatodan keyin yangi kod kerak.
- `403` — raqam kuryerga tegishli yoki mijoz bloklangan.

### 2.4. Manzillar
```http
POST /client/addresses
{ "title": "Uy", "address": "Chilonzor 9-kvartal, 12-uy", "entrance": "2", "floor": "4", "apartment": "38",
  "landmark": "Maktab ro'parasi", "lat": 41.2856, "lng": 69.2034 }
→ 201 { "data": { "id": 1, "title": "Uy", "address": "...", ... } }
```
`PUT /client/addresses/{id}` — to'liq almashtirish (`address` majburiy). `DELETE` — eski buyurtmalarga ta'sir qilmaydi.
`lat`/`lng` ikkalasi birga yoki ikkalasi ham yo'q.

### 2.5. Buyurtma berish
```http
POST /client/orders
Idempotency-Key: 3f0b8d2e-6a51-4c0e-9d7a-1f2b3c4d5e6f
{
  "address": "Yunusobod 4-kvartal, 15-uy", "floor": "4", "apartment": "38", "landmark": "Makro yonida",
  "lat": 41.364, "lng": 69.287,
  "items": [
    { "name": "Kartoshka", "quantity": 3, "unit": "kg" },
    { "name": "Non", "quantity": 4, "unit": "pcs", "note": "issiq bo'lsin" }
  ],
  "payment_method": "cash",
  "note": "Domofon ishlamaydi"
}
```
Saqlangan manzil bilan: `{ "address_id": 1, "items": [...] }` (boshqa manzil maydonlari kerak emas).

```json
// 201
{
  "data": {
    "id": 7, "number": "D000007",
    "status": { "value": "pending", "label": "Yangi" },
    "address": { "address": "Yunusobod 4-kvartal, 15-uy", "entrance": null, "floor": "4", "apartment": "38",
                 "landmark": "Makro yonida", "lat": 41.364, "lng": 69.287 },
    "contact": { "name": "Malika Yusupova", "phone": "+998935550001" },
    "note": "Domofon ishlamaydi",
    "items": [
      { "id": 23, "name": "Kartoshka", "quantity": 3, "unit": {"value":"kg","label":"kg"}, "note": null,
        "status": {"value":"pending","label":"Kutilmoqda"}, "price": null, "courier_note": null }
    ],
    "amounts": { "items_total": 0, "delivery_fee": 15000, "service_fee": 0, "total": 15000 },
    "payment": { "method": {"value":"cash","label":"Naqd (kuryerga)"}, "status": {"value":"unpaid","label":"To'lanmagan"} },
    "receipt_url": null,
    "can_cancel": true,
    "cancel_reason": null,
    "history": [ { "status": {"value":"pending","label":"Yangi"}, "comment": null, "created_at": "2026-11-20T13:56:59+05:00" } ],
    "created_at": "2026-11-20T13:56:59+05:00", "confirmed_at": null, "assigned_at": null, "delivered_at": null, "cancelled_at": null
  }
}
```

**Muhim:**
- **Narx yubormang** — server e'tiborsiz qoldiradi. Yangi buyurtmada `items_total = 0`: mahsulot narxlarini kuryer xariddan keyin kiritadi. Tasdiqlash ekranida "Mahsulot narxi xariddan keyin ma'lum bo'ladi" deb yozing.
- **`Idempotency-Key`:** "Buyurtma berish" bosilganda bitta UUID yarating va javob kelguncha qayta urinishlarda **o'shani** yuboring. Server bir kalit uchun bitta buyurtma yaratadi (qayta so'rovga `200` va o'sha buyurtma).
- `contact_name`/`contact_phone` — boshqa odam qabul qilsa; bo'lmasa profildan olinadi.

### 2.6. Buyurtma holatlari — mijozga nima ko'rsatish

| `status.value` | Ekranda | Tugmalar |
|---|---|---|
| `pending` | "Operator tasdiqlashini kutmoqda" | Bekor qilish |
| `confirmed` | "Tasdiqlandi, kuryer tanlanmoqda" | Bekor qilish |
| `assigned` | "Kuryer: {courier.name}" | Bekor qilish, kuryerga qo'ng'iroq |
| `shopping` | "Kuryer bozorlikda" — mahsulotlar holati jonli (olindi/topilmadi, narx) | Kuryerga qo'ng'iroq |
| `delivering` | "Yo'lda. To'lash uchun: **{amounts.total}** so'm" + chek rasmi | Kuryerga qo'ng'iroq |
| `delivered` | "Yetkazildi" | — |
| `cancelled` | "Bekor qilindi: {cancel_reason}" | — |

- Bekor qilish tugmasini **`can_cancel`** bo'yicha ko'rsating (holatni o'zingiz tekshirmang).
- `courier` maydoni faqat kuryer biriktirilganda keladi.
- Ro'yxatda `items` o'rniga `items_count` keladi; tafsilotda `items` va `history`.

### 2.7. Bekor qilish
```http
POST /client/orders/7/cancel
{ "reason": "Rejalar o'zgardi" }        // ixtiyoriy
→ 200 { "data": { ...status: cancelled, can_cancel: false } }
```
Kuryer bozorlikni boshlagan bo'lsa — `422 "Kuryer bozorlikni boshlagan — bekor qilish uchun operatorga qo'ng'iroq qiling."` → `meta.support_phone` ga qo'ng'iroq tugmasi.

---

## 3. Kuryer ilovasi

### 3.1. Ekran → endpoint

| Ekran | Endpoint |
|---|---|
| Kirish | `POST /auth/courier/login` |
| Bosh ekran ("Ishdaman" switch + faol buyurtmalar) | `PATCH /courier/online`, `GET /courier/orders` |
| Fon xizmati (onlayn paytda) | `POST /courier/location` har 30–60 s |
| Buyurtma | `GET /courier/orders/{id}`, `.../accept`, `.../decline` |
| Bozorlik chek-listi | `PATCH /courier/orders/{id}/items/{item}` |
| Bozorlikni tugatish | `POST .../purchase-complete` (multipart, chek rasmi) |
| Topshirish | `POST .../deliver` |
| Tarix | `GET /courier/orders?scope=history` |

### 3.2. Kirish
```http
POST /auth/courier/login
{ "phone": "+998901110001", "password": "courier123", "device_name": "Samsung A54" }
→ 200
{
  "token": "2|a2KogKTEnLtqe4H9MgskXY1ap6E3PTuhKNhNyo9ye23a7554",
  "user": { "id": 3, "name": "Jasur Karimov", "phone": "+998901110001", "role": "courier",
            "courier": { "vehicle": {"value":"scooter","label":"Skuter / moto"}, "vehicle_number": null,
                         "is_online": true, "last_location_at": "2026-11-20T13:56:58+05:00" },
            "created_at": "2026-11-20T13:56:58+05:00" }
}
```
`401` — telefon/parol noto'g'ri. Admin parolni almashtirsa yoki kuryerni bloklasa — keyingi so'rovda `401`.

### 3.3. Holat va joylashuv
```http
PATCH /courier/online      { "is_online": true }
POST  /courier/location    { "lat": 41.3111, "lng": 69.2797 }
```
Onlayn kuryerlar moderatorga birinchi ko'rinadi, masofa joylashuvdan hisoblanadi (keyingi bosqichda avtomatik taqsimlash ham shunga tayanadi).

### 3.4. Buyurtma bilan ishlash (to'liq oqim)

```
assigned ──accept──► shopping ──(har mahsulot belgilanadi)──► purchase-complete ──► delivering ──deliver──► delivered
    └──decline──► (moderatorga qaytadi, ro'yxatdan yo'qoladi)
```

**1. Qabul qilish:** `POST /courier/orders/9/accept` → `status: shopping`. Mijozga "Kuryer bozorlikda" xabari ketadi.

**2. Rad etish (faqat `assigned`):** `POST /courier/orders/9/decline` `{ "reason": "Mashina buzildi" }` → `{ "message": "Buyurtma moderatorga qaytarildi." }`.

**3. Mahsulotlarni belgilash (faqat `shopping`):**
```http
PATCH /courier/orders/9/items/28
{ "status": "purchased", "price": 18000, "courier_note": null }
→ 200 { "data": { "id": 28, "name": "Bodring", ..., "status": {"value":"purchased","label":"Olindi"}, "price": 18000 } }

PATCH /courier/orders/9/items/29
{ "status": "unavailable", "courier_note": "Do'konda tugagan" }
```
- `price` — shu **qator uchun to'langan jami** summa (3 kg uchun, 1 kg emas).
- Xato kiritilsa — qayta yuborib tuzatish mumkin (bozorlik tugaguncha).
- Joriy jami summani ko'rsatish uchun: `sum(items[status=purchased].price)` yoki `GET` qilib `amounts` dan oling.

**4. Bozorlikni tugatish:**
```http
POST /courier/orders/9/purchase-complete
Content-Type: multipart/form-data
receipt=<chek.jpg>            // ixtiyoriy; jpg/png/webp, ≤ 5 MB
→ 200 { "data": { "status": {"value":"delivering", ...}, "amounts": { "items_total": 54000, "delivery_fee": 15000, "service_fee": 0, "total": 69000 }, "receipt_url": "..." } }
```
- Tugmani hamma mahsulot belgilanmaguncha o'chirib qo'ying. Aks holda `422 "Barcha mahsulotlarni belgilang: olindi yoki topilmadi."`
- Hech narsa olinmagan bo'lsa — `422`; kuryer moderatorga qo'ng'iroq qiladi.

**5. Topshirish:** ekranda katta shriftda `amounts.total` → naqd oladi → `POST /courier/orders/9/deliver` → `status: delivered`, `payment.status: paid`.

Kuryer javobida mijoz kontakti: `client` (`name`, `phone`) va `contact` (qabul qiluvchi — boshqa odam bo'lishi mumkin). Qo'ng'iroq uchun `contact.phone` ni ishlating.

---

## 4. Ikkala ilova uchun umumiy

### 4.1. Profil
`GET /me`, `PATCH /me` `{ "name": "..." }`, `POST /auth/logout` (joriy token bekor bo'ladi).

### 4.2. Bildirishnomalar
```http
GET /notifications?limit=20
→ { "data": [ { "id": "11a4359a-...", "title": "Buyurtma tasdiqlandi",
                "body": "D000002 buyurtmangiz qabul qilindi. Tez orada kuryer biriktiriladi.",
                "order_id": 2, "read": false, "created_at": "..." } ],
    "links": {...}, "meta": {...}, "unread_count": 3 }

POST /notifications/{id}/read
POST /notifications/read-all
```
Bosilganda `order_id` bo'yicha buyurtma ekraniga o'ting.

### 4.3. Push (FCM)
Push serverda keyingi bosqichda yoqiladi, lekin tokenni **hozirdan** yuboring:
```http
POST   /devices   { "token": "<fcm token>", "platform": "android" }    // har kirishda va token yangilanganda
DELETE /devices   { "token": "<fcm token>" }                            // logout'dan oldin
```
Push payload `data`: `{ "order_id": "7", "status": "shopping" }`.

### 4.4. Yangilanishlarni olish (push yoqilguncha)
- Buyurtma ekrani ochiq bo'lsa — `GET /.../orders/{id}` har 15–20 soniyada.
- Bosh ekranda — `GET /notifications?limit=1` har 30–60 soniyada (`unread_count`).
- Ilova fonga o'tganda polling'ni to'xtating.

---

## 5. Test rejimi

| | |
|---|---|
| SMS kod | Har doim `1111` (SMS yuborilmaydi, server logiga yoziladi) |
| Mijozlar | `+998935550001`, `+998935550002`, `+998935550003` (yoki istalgan yangi raqam) |
| Kuryerlar | `+998901110001` … `+998901110003`, parol `courier123` |
| Admin panel | `/admin` — moderator `+998900000002` / `moderator123` |

Buyurtma kuryerga borishi uchun admin panelda moderator uni **tasdiqlashi** va **kuryerni tanlashi** kerak.

## 6. Postman

[postman/](postman/) papkasida:
- `Delivery.postman_collection.json` — 32 ta so'rov, papkalar bo'yicha (avtorizatsiya, umumiy, mijoz, kuryer).
- `Delivery.local.postman_environment.json`, `Delivery.test.postman_environment.json` — `base_url`, test raqamlari.

Import → environment tanlang → `1. Avtorizatsiya` ni ishga tushiring: `client_token`, `courier_token`, keyin `order_id`, `item_id` va boshqalar avtomatik saqlanadi. Har so'rovda status kodi testi bor.

## 7. O'zgarishlar tarixi

| Versiya | Sana | O'zgarish |
|---|---|---|
| 1.0.0 | 2026-11-20 | Birinchi versiya: mijoz va kuryer API, bildirishnomalar, qurilma tokenlari |

Kontrakt buzadigan o'zgarish bo'lsa `/api/v2` ochiladi; v1 ga faqat yangi ixtiyoriy maydonlar qo'shiladi — ilova noma'lum maydonlarni e'tiborsiz qoldirsin.

# Delivery — bozorlik va yetkazib berish (Laravel backend + admin panel)

Laravel 12 · PHP ^8.2 (lokal 8.5) · Sanctum · MySQL (MAMP, port 8889) · Blade admin (build qadamisiz).
Foydalanuvchi bilan **o'zbek tilida**; UI, API xabarlari va kod izohlari o'zbekcha (lotin), identifikatorlar inglizcha.

Ishtirokchilar: **mijoz** (mobil, SMS-OTP), **kuryer** (mobil, telefon+parol), **moderator** va **administrator** (admin panel). Vendor yo'q.
Oqim: mijoz ro'yxat yuboradi → moderator tasdiqlaydi → kuryerni qo'lda tanlaydi → kuryer qabul qiladi, narxlarni kiritadi → yetkazadi (naqd).
Andoza: BeeExpress (`../beee-express`). Admin dizayni: Daftaron (`../daftaron`) token tizimi, brend rangi boshqa.

## Hujjatlar (biznes qoida yoki kontrakt o'zgarsa — yangilang)

| Fayl | Kim uchun | Nima |
|---|---|---|
| `TZ.md` | buyurtmachi, jamoa | Biznes qoidalar, rollar, ekranlar, bosqichlar, ochiq savollar (§17) |
| `docs/api.md` | mobil dasturchilar | Oqimlar, ekran→endpoint, UI tavsiyalari, xatolar |
| `docs/openapi.yaml` | Swagger (`/docs/api`) | Aniq kontrakt — **yagona manba** |
| `docs/postman/*` | QA, mobil | `tools/postman/build_collection.py` dan yaratiladi — qo'lda tahrirlamang |
| `docs/order-status-flow.md`, `docs/architecture.md` | backend | Holatlar grafi, qatlamlar, kengaytirish nuqtalari |

`tests/Feature/ApiDocsTest` — route ↔ openapi ↔ Postman va javob maydonlari ↔ sxema sinxronligini tekshiradi.

## Buyruqlar

```bash
vendor/bin/phpunit                                   # SQLite :memory:, ~1 s
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=8889 DB_DATABASE=delivery_test DB_USERNAME=root DB_PASSWORD=root vendor/bin/phpunit
vendor/bin/pint
php artisan migrate:fresh --seed                     # lokal `delivery` bazasi, demo ma'lumot
php artisan serve                                    # /admin, /docs/api
python3 tools/postman/build_collection.py docs/postman
```
Testlarni hech qachon `delivery` (dev) bazasiga ulamang — faqat `delivery_test`.
Demo akkauntlar: admin `+998900000001`/`admin12345`, moderator `+998900000002`/`moderator123`, kuryer `+998901110001`/`courier123`, mijoz OTP `1111`.

## Arxitektura (bitta manba)

| Nima | Qayerda | Qoida |
|---|---|---|
| Holat grafi | `Enums\OrderStatus::allowedTransitions()` | Yangi holat — shu yerda + `OrderTransitioner::TIMESTAMPS` + docs |
| Holat o'zgarishi | `Services\Orders\OrderTransitioner` | Statusni boshqa joyda yozmang; tarix + event shu yerda |
| Biznes amallar | `Actions\Orders\*` | Har biri: `DB::transaction` + `lock()`; kuryer amallari `ensureAssignedTo()` |
| Summalar | `Services\Orders\OrderTotals` | `total = items_total + delivery_fee + service_fee`, faqat serverda |
| Sozlamalar | `Services\Settings\SettingsRepository` | Standart — `config/delivery.php` `defaults`; yangi kalit avval u yerga |
| Drayverlar | `Contracts\*`, bind — `AppServiceProvider` | To'lov `PaymentGatewayManager`, narx `DeliveryPricing`, SMS, push |
| Kuryer tanlash | `Services\Couriers\CourierLocator` | Avtomatik taqsimlash (2-bosqich) shu ro'yxatdan |
| Bildirishnomalar | `Listeners\SendOrderStatusNotifications`, `Notifications\OrderStatusNotification` | database + `PushChannel` |
| Xatolar | `bootstrap/app.php` | `BusinessRuleException` → API 422 / web flash; API 404 → "Topilmadi." |

## Majburiy konventsiyalar

- Klientga ishonmang: narx, rol, egalik faqat serverda. Mijoz/kuryer resursi `$user->clientOrders()/courierOrders()->findOrFail()` orqali (boshqasiniki 404).
- Admin route'larida foydalanuvchi parametrlari `Route::bind('courier'|'client'|'staff')` bilan rolga bog'langan.
- Fayl yuklash faqat FormRequest'da `file|image|mimes:jpg,jpeg,png,webp|max:5120`.
- Yangi validatsiya qoidasi/maydon → `lang/uz/validation.php` ga xabar/atribut (aks holda kalit nomi chiqadi).
- `env()` faqat `config/*` da. `local`/`testing` muhitiga bog'liq ruxsat bypass'i yozmang.
- `Model::shouldBeStrict()` lokalda yoqilgan: view'lardagi relation'larni eager-load qiling.
- Yangi model yaratganda DB default'iga tayanadigan atributni (`is_active` va h.k.) aniq bering — model qayta o'qilmaydi.
- Pagination `limit` 1..100 (`ApiController::perPage`).
- Route'larni aniq metod bilan yozing (`apiResource` `PUT|PATCH` ikkalasini ochadi).
- Xato tuzatilsa — avval yiqiladigan test; yangi admin sahifasi → `AdminPanelTest::test_all_pages_render_for_admin` ga URL.

## Skill'lar (`.claude/skills/`)

- `api-endpoint` — mobil API endpoint qo'shish/o'zgartirish (+ openapi, Postman, api.md)
- `order-flow` — holatlar, amallar, summalar, bildirishnomalar, to'lov drayverlari
- `admin-page` — admin panel sahifasi, dizayn tokenlari, komponentlar
- `docs` — TZ, api.md, Swagger, Postman'ni yangilash
- `verify` — ishni tugatishdan oldingi tekshiruvlar

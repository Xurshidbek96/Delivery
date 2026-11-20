# Delivery — bozorlik va yetkazib berish (Laravel backend + admin panel)

Laravel 12 · PHP ^8.2 (lokal 8.5) · Sanctum · MySQL (MAMP, port 8889) · Blade admin (build qadamisiz).
Foydalanuvchi bilan **o'zbek tilida**; UI, xabarlar va kod izohlari o'zbekcha (lotin), identifikatorlar inglizcha.
BeeExpress (`../beee-express`) andozasida, lekin vendor yo'q: faqat mijoz, kuryer, moderator/admin.
Admin dizayni Daftaron (`../daftaron`) token tizimidan olingan, brend rangi boshqa (`public/css/admin.css` `:root`).

Hujjatlar: `docs/api.md` (mobil API), `docs/architecture.md`, `docs/order-status-flow.md`.

## Buyruqlar

```bash
vendor/bin/phpunit                                   # SQLite :memory:, ~1 s
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=8889 DB_DATABASE=delivery_test DB_USERNAME=root DB_PASSWORD=root vendor/bin/phpunit
vendor/bin/pint
php artisan migrate:fresh --seed                     # lokal `delivery` bazasi, demo ma'lumot
```
Testlarni hech qachon `delivery` (dev) bazasiga ulamang — faqat `delivery_test`.

## Arxitektura (bitta manba)

| Nima | Qayerda | Qoida |
|---|---|---|
| Holat grafi | `Enums\OrderStatus::allowedTransitions()` | Yangi holat — shu yerda + `OrderTransitioner::TIMESTAMPS` + docs |
| Holat o'zgarishi | `Services\Orders\OrderTransitioner` | Statusni boshqa joyda yozmang; tarix + event shu yerda |
| Biznes amallar | `Actions\Orders\*` | Har biri: `DB::transaction` + `lock()`; kuryer amallari `ensureAssignedTo()` |
| Summalar | `Services\Orders\OrderTotals` | `total = items_total + delivery_fee + service_fee`, faqat serverda |
| Sozlamalar | `Services\Settings\SettingsRepository` | Standart — `config/delivery.php` `defaults`; yangi kalit avval u yerga |
| Drayverlar | `Contracts\*`, bind — `AppServiceProvider` | To'lov `PaymentGatewayManager`, narx `DeliveryPricing`, SMS, push |
| Kuryer tanlash | `Services\Couriers\CourierLocator` | Avtomatik taqsimlash shu ro'yxatdan |
| Xatolar | `bootstrap/app.php` | `BusinessRuleException` → API 422 / web flash; API 404 → "Topilmadi." |

## Majburiy konventsiyalar

- Klientga ishonmang: narx, rol, egalik faqat serverda. Mijoz/kuryer resursi `$user->clientOrders()/courierOrders()->findOrFail()` orqali (boshqasiniki 404).
- Admin route'larida foydalanuvchi parametrlari `Route::bind('courier'|'client'|'staff')` bilan rolga bog'langan.
- Fayl yuklash faqat FormRequest'da `file|image|mimes:jpg,jpeg,png,webp|max:5120`.
- Yangi validatsiya qoidasi/maydon → `lang/uz/validation.php` ga xabar/atribut qo'shing (aks holda kalit chiqadi).
- `env()` faqat `config/*` da. `local`/`testing` muhitiga bog'liq ruxsat bypass'i yozmang.
- `Model::shouldBeStrict()` lokalda yoqilgan: view'larda ishlatiladigan relation'larni eager-load qiling.
- Yangi model yaratganda DB default'iga tayanadigan atributni (`is_active` va h.k.) aniq bering — model qayta o'qilmaydi.
- Pagination `limit` 1..100 (`ApiController::perPage`).
- Xato tuzatilsa — avval yiqiladigan test; yangi admin sahifasi → `AdminPanelTest::test_all_pages_render_for_admin` ga URL.

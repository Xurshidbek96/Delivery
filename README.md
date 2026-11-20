# Delivery

Bozorlik va yetkazib berish xizmati: mijoz ilovada xaridlar ro'yxatini yuboradi → moderator admin panelda tasdiqlaydi va kuryerni tanlaydi → kuryer bozordan sotib olib, mijozga yetkazadi.

- **Mijoz ilovasi** va **kuryer ilovasi** — REST API (`/api/v1`, Laravel Sanctum)
- **Admin panel** — `/admin` (moderator va administrator)

| Hujjat | Kim uchun |
|---|---|
| [TZ.md](TZ.md) | Texnik topshiriq — buyurtmachi va jamoa |
| [docs/api.md](docs/api.md) | Mobil dasturchilar qo'llanmasi |
| Swagger — `/docs/api` ([docs/openapi.yaml](docs/openapi.yaml)) | Interaktiv API spetsifikatsiyasi |
| [docs/postman/](docs/postman/) | Postman collection + environment (generator: `tools/postman/build_collection.py`) |
| [docs/architecture.md](docs/architecture.md), [docs/order-status-flow.md](docs/order-status-flow.md) | Backend dasturchilar |

Stek: Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade (build qadamisiz). To'lov: hozircha naqd; Click/Payme uchun joy tayyor.

## O'rnatish (lokal, MAMP)

```bash
composer install
cp .env.example .env && php artisan key:generate
# .env: DB_DATABASE=delivery, DB_PORT=8889 (MAMP)
php artisan migrate --seed      # demo ma'lumot bilan
php artisan storage:link
php artisan serve               # http://127.0.0.1:8000/admin
```

| Rol | Login | Parol |
|---|---|---|
| Administrator | `+998900000001` | `admin12345` |
| Moderator | `+998900000002` | `moderator123` |
| Kuryer (API) | `+998901110001` | `courier123` |
| Mijoz (API) | `+998935550001` | SMS kod — test rejimida `1111` |

## Testlar

```bash
vendor/bin/phpunit                    # SQLite :memory:
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=8889 DB_DATABASE=delivery_test DB_USERNAME=root DB_PASSWORD=root vendor/bin/phpunit
vendor/bin/pint                       # kod formati
```

## Serverga chiqarish (test rejimi)

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, baza ma'lumotlari.
   Test rejimida `OTP_FIXED_CODE=1111` qoldirish mumkin; **haqiqiy foydalanuvchilar kelishidan oldin bo'sh qiling** va SMS drayverini ulang.
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force && php artisan db:seed --class=StaffSeeder --force` (faqat administrator; demo kerak bo'lsa `db:seed --force`)
4. `php artisan storage:link && php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. Web root → `public/`. Swagger (`/docs/api`) kerak bo'lmasa `API_DOCS_ENABLED=false`. Navbat ishchisi hozir shart emas (bildirishnomalar sinxron).
6. Administrator bilan kirib parolni darhol almashtiring (Profil).

## Avtodeploy (https://delivery-5-9-0-240.sslip.io)

`main` ga push → GitHub Actions ([.github/workflows/deploy.yml](.github/workflows/deploy.yml)): Pint + testlar (SQLite va MariaDB) → o'tsa SSH orqali serverda [deploy/deploy.sh](deploy/deploy.sh) ishga tushadi (`git merge --ff-only`, `composer install --no-dev`, `migrate --force`, `optimize`). PR'larda faqat testlar.

- Server: HestiaCP `ruslan.firstcoders.uz`, foydalanuvchi `beeeexpress`, papka `~/web/delivery-5-9-0-240.sslip.io/public_html` (nginx `laravel` shabloni, PHP 8.4), baza `beeeexpress_delivery`.
- Domen: `firstcoder.uz` zonasiga DNS yozuv qo'shish imkoni bo'lmagani uchun [sslip.io](https://sslip.io) (IP'dan avtomatik DNS) ishlatilgan; SSL — Let's Encrypt (Hestia avtomatik yangilaydi). Haqiqiy domenga o'tish: A-yozuv → `5.9.0.240`, Hestia'da domenni qo'shib papkani ko'chirish, `.env` `APP_URL`, `authorized_keys` dagi `command=` yo'li va workflow `url`.
- Secret'lar: `DEPLOY_SSH_KEY`, `DEPLOY_KNOWN_HOSTS`. Serverdagi bu kalit `authorized_keys` da `command=` bilan cheklangan — faqat `deploy.sh` ni bajaradi.
- `.env` va `storage/` serverda turadi, repoga kirmaydi. Serverda tracked faylni qo'lda o'zgartirsangiz deploy to'xtaydi (jimgina yo'qolmasligi uchun).
- Qo'lda qayta deploy: GitHub → Actions → Delivery → Run workflow.

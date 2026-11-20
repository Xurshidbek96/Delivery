---
name: verify
description: Delivery'da ishni tugatishdan oldin tekshirish — testlar (SQLite va MySQL), Pint, OpenAPI YAML va hujjat sinxronligi, xavfsizlik ro'yxati, brauzerda admin panel. Har qanday kod o'zgarishidan keyin ishlating, shuningdek foydalanuvchi "tekshir", "test qil", "ishlayaptimi", "tayyormi", "verify", "check", "audit" desa.
---

# Tekshiruvlar

Har birini ishga tushiring va natijani **aniq** ayting (nechta test, qaysi biri yiqildi, chiqish bilan). "Ishlashi kerak" demang.

## 1. Avtomatik

```bash
vendor/bin/pint --test
vendor/bin/phpunit
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=8889 DB_DATABASE=delivery_test DB_USERNAME=root DB_PASSWORD=root vendor/bin/phpunit
php -r 'require "vendor/autoload.php"; Symfony\Component\Yaml\Yaml::parseFile("docs/openapi.yaml"); echo "openapi ok\n";'
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize:clear
```

- MySQL ishlamasa: `nc -z 127.0.0.1 8889`; MAMP'ni yoqishni foydalanuvchidan so'rang. SQLite noma'lum ustunni sezmaydi — MySQL natijasisiz "tayyor" demang.
- Xato tuzatilgan bo'lsa: test tuzatishsiz yiqilishini bir marta ko'ring.
- `ApiDocsTest` yiqilsa — `docs` skill'i (openapi/Postman yangilanmagan).

## 2. Diff bo'yicha ko'z bilan

- [ ] Klientdan kelgan narx/`status`/`role`/`*_id` to'g'ridan-to'g'ri yozilmaydi.
- [ ] ID bilan kelgan resurs scoped so'rov orqali (IDOR yo'q), admin'da rolga bog'langan binding.
- [ ] Holat faqat `OrderTransitioner` orqali, Action ichida `lock()`.
- [ ] Fayl yuklash `image|mimes|max`.
- [ ] Yangi `env()` faqat `config/*` da va `.env.example` da (sirsiz).
- [ ] `local`/`testing` ga bog'liq ruxsat bypass yo'q.
- [ ] Ro'yxatlar `with()` + `limit` 1..100.
- [ ] Xabarlar o'zbekcha; yangi validatsiya maydoni `lang/uz/validation.php` da.

## 3. Qo'lda (UI o'zgargan bo'lsa)

`php artisan serve` → `/admin` (admin `+998900000001` / `admin12345`): o'zgargan sahifa desktop va 390px kenglikda, tungi rejimda. API o'zgargan bo'lsa — curl yoki Swagger "Try it out".

## 4. Hujjatlar

API → `docs/openapi.yaml`, Postman generatori, `docs/api.md`. Biznes qoida → `TZ.md`, `docs/order-status-flow.md`. Arxitektura → `docs/architecture.md`, `.claude/CLAUDE.md`.

## 5. Hisobot

Qisqa: nima o'zgardi (fayl havolalari), testlar (SQLite N/N, MySQL N/N), foydalanuvchi qilishi kerak bo'lgan ish.

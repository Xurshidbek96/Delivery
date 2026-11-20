---
name: docs
description: Delivery hujjatlarini yaratish yoki yangilash — TZ.md (texnik topshiriq), docs/api.md (mobil qo'llanma), docs/openapi.yaml (Swagger), Postman collection/environment, order-status-flow, architecture. Ishlating, agar foydalanuvchi "TZ", "texnik topshiriq", "hujjat", "dokumentatsiya", "swagger", "openapi", "postman", "api.md", "mobilchilarga", "buyurtmachiga" desa.
---

# Hujjatlar

| Fayl | Auditoriya | Qachon yangilanadi |
|---|---|---|
| `TZ.md` | buyurtmachi + jamoa (texnik bo'lmagan til) | biznes qoida, ekran, rol, bosqich o'zgarsa. ✅/🔜/❓ belgilarini to'g'ri tuting; kelishilmagan narsa → §17 "Ochiq savollar" |
| `docs/api.md` | mobil dasturchilar | endpoint, oqim, xato, UI tavsiyasi o'zgarsa |
| `docs/openapi.yaml` | Swagger UI `/docs/api` | har qanday kontrakt o'zgarishida — yagona manba |
| `docs/postman/*` | QA, mobil | `tools/postman/build_collection.py` ni tahrirlab qayta yarating (JSON'ni qo'lda o'zgartirmang) |
| `docs/order-status-flow.md` | backend | holat/o'tish/summa qoidasi |
| `docs/architecture.md` | backend | qatlam, drayver, xavfsizlik qoidasi |
| `.claude/CLAUDE.md` | Claude | konventsiya yoki buyruq o'zgarsa |

## Ish tartibi

1. Kodni manba deb oling: route'lar (`php artisan route:list --path=api`), FormRequest qoidalari, Resource maydonlari. Hujjatda kodda yo'q narsani "bor" demang — rejadagisi 🔜.
2. Misollar haqiqiy bo'lsin: server ishga tushiring (`php artisan migrate:fresh --seed && php artisan serve`) va javobni curl bilan oling.
3. OpenAPI: `components` dagi umumiy `responses`/`schemas` ni qayta ishlating. Qiymatida `: ` bo'lgan tavsifni tirnoqqa oling. Tekshirish:
   `php -r 'require "vendor/autoload.php"; Symfony\Component\Yaml\Yaml::parseFile("docs/openapi.yaml"); echo "ok\n";'`
4. Postman: `python3 tools/postman/build_collection.py docs/postman`; jonli tekshiruv — `tools/postman/smoke_run.py` (fayl boshidagi izoh).
5. `vendor/bin/phpunit --filter ApiDocsTest` — route↔openapi↔Postman va javob maydonlari↔sxema.
6. Swagger UI'ni brauzerda oching (`/docs/api`): xato paneli yo'qligini va bitta "Try it out" ishlashini ko'ring.

Til: o'zbek (lotin), qisqa jumlalar, jadval ko'p. Har hujjat boshida kim uchunligi va bog'liq hujjatlar.

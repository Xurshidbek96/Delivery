---
name: api-endpoint
description: Delivery mobil REST API'ga (/api/v1) endpoint qo'shish yoki mavjudini o'zgartirish — route, FormRequest, controller, Resource, rol middleware, egalik, test, OpenAPI, Postman va api.md. Ishlating, agar foydalanuvchi "endpoint", "API", "route", "marshrut", "mijoz ilovasi uchun", "kuryer ilovasi uchun", "mobil uchun", "javobga maydon qo'sh", "swagger", "yangi so'rov" desa.
---

# API endpoint

## Qayerga

| Qism | Joy | Naqsh |
|---|---|---|
| Route | `routes/api.php` | Guruhlar: public (`meta`, `auth/*` + `throttle`), `role:client,courier` (umumiy), `role:client` → `client/*`, `role:courier` → `courier/*`. Metodni aniq yozing (`apiResource` emas), `whereNumber()` |
| Validatsiya | `app/Http/Requests/Api/{Auth,Client,Courier}/` | Telefon — `NormalizesPhone`; murakkab kirish — `toData()` DTO (`App\Data\*`) |
| Controller | `app/Http/Controllers/Api/V1/{Auth,Client,Courier}/` | `ApiController` dan; yupqa — biznes mantiq `Actions\*` yoki `Services\*` da |
| Javob | `app/Http/Resources/*Resource.php` | Lug'at → `['value' => ..., 'label' => ...]`; `whenLoaded`/`whenCounted`; ichki maydonlar (`staff_note`) chiqmaydi |
| Xato | `throw new BusinessRuleException('O\'zbekcha xabar')` | → 422 `{message}` |

## Majburiy

- **Egalik:** resurs `$request->user()->clientOrders()->findOrFail($id)` / `courierOrders()` / `addresses()` orqali — boshqasiniki 404. Validatsiyadagi ID: `Rule::exists(...)->where('user_id', $this->user()?->id)`. Yalang'och `Model::findOrFail($id)` — IDOR.
- **Klient qiymatlari:** narx, `status`, `role`, `client_id`, `courier_id` so'rovdan yozilmaydi. Faqat `validated()`.
- **Ro'yxat:** `->paginate($this->perPage($request))`, `with()`/`withCount()` (N+1 yo'q).
- **Fayl:** `file|image|mimes:jpg,jpeg,png,webp|max:5120`.
- **Yaratish (pul/buyurtma):** `Idempotency-Key`, kesh kaliti foydalanuvchiga bog'langan (`idem:order:{userId}:...`).
- **Auth'siz endpoint** — route faylida nega ekanini izohlang va `throttle` qo'shing.
- Yangi validatsiya maydoni → `lang/uz/validation.php` `attributes`.

## Test (`tests/Feature/Api/`)

Muvaffaqiyat + boshqa foydalanuvchi resursi (404) + noto'g'ri rol (403) + validatsiya (422). Yordamchilar: `$this->actingAsApi($user)`, `$this->placeOrder($client)`, `User::factory()->courier()/client()/moderator()`.

## Hujjat (ApiDocsTest yiqilmasligi uchun majburiy)

1. `docs/openapi.yaml` — path, `operationId`, so'rov/javob sxemasi, xatolar (`$ref: '#/components/responses/...'`). Tavsifda `: ` bo'lsa qiymatni tirnoqqa oling.
2. `tools/postman/build_collection.py` ga `req(...)` → `python3 tools/postman/build_collection.py docs/postman`.
3. `docs/api.md` — ekran→endpoint jadvali va misol; kontrakt buzilsa "O'zgarishlar tarixi".
4. Biznes qoida bo'lsa — `TZ.md`.

Tugatgach — `verify` skill'i.

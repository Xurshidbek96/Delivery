"""Postman collection va environment'larni yaratadi.

Ishlatish (loyiha ildizidan): python3 tools/postman/build_collection.py docs/postman
Yangi endpoint qo'shilsa — shu faylga req(...) qo'shing va qayta ishga tushiring.
tests/Feature/ApiDocsTest collection barcha route'larni qamrab olganini tekshiradi.
"""
import json, uuid, sys
OUT = sys.argv[1] if len(sys.argv) > 1 else 'docs/postman'
NS = uuid.UUID('6b1f8a52-1c1e-4f0a-9a43-3d7c2b9e0d11')
uid = lambda s: str(uuid.uuid5(NS, s))

def script(lines, kind='test'):
    return {"listen": kind, "script": {"type": "text/javascript", "exec": lines}}

def expect(code):
    return [f"pm.test('HTTP {code}', () => pm.response.to.have.status({code}));"]

def req(name, method, path, body=None, desc='', tests=None, code=200, query=None, headers=None, prerequest=None, form=None, auth=None):
    url_raw = '{{base_url}}' + path + ('?' + '&'.join(f"{k}={v}" for k, v, *_ in query) if query else '')
    url = {"raw": url_raw, "host": ["{{base_url}}"], "path": [p for p in path.strip('/').split('/')]}
    if query:
        url["query"] = [{"key": k, "value": v, "description": d[0] if d else "", **({"disabled": True} if d and len(d) > 1 else {})} for k, v, *d in query]
    h = [{"key": "Accept", "value": "application/json"}]
    if body is not None:
        h.append({"key": "Content-Type", "value": "application/json"})
    for k, v in (headers or []):
        h.append({"key": k, "value": v})
    r = {"method": method, "header": h, "url": url, "description": desc}
    if body is not None:
        r["body"] = {"mode": "raw", "raw": json.dumps(body, ensure_ascii=False, indent=2), "options": {"raw": {"language": "json"}}}
    if form is not None:
        r["body"] = {"mode": "formdata", "formdata": form}
    if auth == 'none':
        r["auth"] = {"type": "noauth"}
    ev = [script(expect(code) + (tests or []))]
    if prerequest:
        ev.append(script(prerequest, 'prerequest'))
    return {"id": uid(name + path + method), "name": name, "event": ev, "request": r}

def folder(name, items, desc='', token=None):
    f = {"id": uid('folder' + name), "name": name, "description": desc, "item": items}
    if token:
        f["auth"] = {"type": "bearer", "bearer": [{"key": "token", "value": "{{" + token + "}}", "type": "string"}]}
    return f

save = lambda var, expr: f"if (pm.response.code < 300) {{ pm.collectionVariables.set('{var}', {expr}); }}"

auth = folder("1. Avtorizatsiya", [
    req("Mijoz — SMS kod yuborish", "POST", "/auth/otp/send", {"phone": "{{client_phone}}"}, auth='none',
        desc="Test rejimida SMS yuborilmaydi, kod `{{otp_code}}` (1111). Bir raqamga 60 soniyada bir marta.",
        tests=["pm.test('expires_in bor', () => pm.expect(pm.response.json().expires_in).to.be.a('number'));"]),
    req("Mijoz — kodni tasdiqlash (token)", "POST", "/auth/otp/verify",
        {"phone": "{{client_phone}}", "code": "{{otp_code}}", "name": "Postman Mijoz", "device_name": "Postman"}, auth='none',
        desc="Token avtomatik `client_token` ga saqlanadi.",
        tests=[save('client_token', 'pm.response.json().token'),
               "pm.test('rol: client', () => pm.expect(pm.response.json().user.role).to.eql('client'));"]),
    req("Kuryer — kirish (token)", "POST", "/auth/courier/login",
        {"phone": "{{courier_phone}}", "password": "{{courier_password}}", "device_name": "Postman"}, auth='none',
        desc="Token avtomatik `courier_token` ga saqlanadi. Login/parolni admin panel → Kuryerlar bo'limida administrator beradi.",
        tests=[save('courier_token', 'pm.response.json().token'),
               "pm.test('rol: courier', () => pm.expect(pm.response.json().user.role).to.eql('courier'));"]),
], "Avval shu papkani ishga tushiring — tokenlar o'zgaruvchilarga saqlanadi.")

common = folder("2. Umumiy (mijoz tokeni bilan)", [
    req("Ilova sozlamalari (meta)", "GET", "/meta", auth='none',
        tests=["pm.test('birliklar ro\\'yxati', () => pm.expect(pm.response.json().units).to.be.an('array').that.is.not.empty);"]),
    req("Profil", "GET", "/me"),
    req("Ismni o'zgartirish", "PATCH", "/me", {"name": "Malika Yusupova"}),
    req("Bildirishnomalar", "GET", "/notifications", query=[("limit", "20", "1..100"), ("page", "1", "", True)],
        tests=["pm.test('unread_count bor', () => pm.expect(pm.response.json()).to.have.property('unread_count'));",
               "const n = pm.response.json().data[0]; if (n) pm.collectionVariables.set('notification_id', n.id);"]),
    req("Bildirishnomani o'qildi qilish", "POST", "/notifications/{{notification_id}}/read"),
    req("Hammasini o'qildi qilish", "POST", "/notifications/read-all"),
    req("Push tokenini saqlash", "POST", "/devices", {"token": "postman-fcm-token", "platform": "android"}),
    req("Push tokenini o'chirish", "DELETE", "/devices", {"token": "postman-fcm-token"}),
], "Bu endpointlar kuryer uchun ham bir xil — kuryer sifatida tekshirish uchun papka Authorization'ida `{{courier_token}}` qo'ying.", token='client_token')

client = folder("3. Mijoz ilovasi", [
    folder("Manzillar", [
        req("Manzillar ro'yxati", "GET", "/client/addresses"),
        req("Manzil qo'shish", "POST", "/client/addresses",
            {"title": "Uy", "address": "Navoiy ko'chasi, 12-uy", "entrance": "2", "floor": "4", "apartment": "38", "landmark": "Maktab ro'parasi", "lat": 41.0012, "lng": 71.6698},
            code=201, tests=[save('address_id', 'pm.response.json().data.id')]),
        req("Manzilni yangilash", "PUT", "/client/addresses/{{address_id}}",
            {"title": "Uy", "address": "Navoiy ko'chasi, 14-uy", "floor": "5", "lat": 41.0012, "lng": 71.6698}),
        req("Manzilni o'chirish", "DELETE", "/client/addresses/{{address_id}}"),
    ]),
    folder("Katalog", [
        req("Do'konlar", "GET", "/client/shops",
            query=[("q", "", "Nom bo'yicha qidiruv", True), ("limit", "20", "1..100")],
            desc="Faqat faol do'konlar. Birinchisi `shop_id` ga saqlanadi.",
            tests=["const s = pm.response.json().data[0]; if (s) pm.collectionVariables.set('shop_id', s.id);"]),
        req("Do'kon", "GET", "/client/shops/{{shop_id}}", desc="Nofaol do'kon — 404."),
        req("Mahsulotlar", "GET", "/client/products",
            query=[("shop_id", "{{shop_id}}", "Bitta do'kon mahsulotlari"), ("q", "", "Nom bo'yicha qidiruv", True), ("limit", "20", "1..100")],
            desc="Faqat faol mahsulotlar (do'koni ham faol). `price` — taxminiy, 1 birlik uchun. Birinchisi `product_id` ga saqlanadi.",
            tests=["const p = pm.response.json().data[0]; if (p) pm.collectionVariables.set('product_id', p.id);"]),
        req("Mahsulot", "GET", "/client/products/{{product_id}}", desc="Mahsulot yoki do'koni nofaol — 404."),
    ]),
    folder("Buyurtmalar", [
        req("Buyurtma berish (manzil bilan)", "POST", "/client/orders", {
            "address": "Uychi ko'chasi, 15-uy", "floor": "4", "apartment": "38", "landmark": "Chorsu bozori yonida",
            "lat": 41.005, "lng": 71.66,
            "items": [{"name": "Kartoshka", "quantity": 3, "unit": "kg"},
                      {"name": "Non", "quantity": 4, "unit": "pcs", "note": "issiq bo'lsin"},
                      {"name": "Sut 3.2%", "quantity": 2, "unit": "l"}],
            "payment_method": "cash", "note": "Domofon ishlamaydi"},
            code=201, headers=[("Idempotency-Key", "{{idempotency_key}}")],
            prerequest=["// Har yuborishda yangi kalit. Qayta yuborishni sinash uchun shu qatorni o'chirib qo'ying.",
                        "pm.collectionVariables.set('idempotency_key', pm.variables.replaceIn('{{$guid}}'));"],
            desc="Narx yuborilmaydi — `delivery_fee` serverda, mahsulot narxlarini kuryer kiritadi. Javobdagi `id` → `order_id`.\n\n"
                 "Keyingi qadam: admin panelda (moderator) buyurtmani **tasdiqlang** va kuryerni (`+998901110001`) **tanlang**, so'ng 4-papkaga o'ting.",
            tests=[save('order_id', 'pm.response.json().data.id'),
                   "pm.test('holat: pending', () => pm.expect(pm.response.json().data.status.value).to.eql('pending'));",
                   "pm.test('xarid summasi hali 0', () => pm.expect(pm.response.json().data.amounts.items_total).to.eql(0));"]),
        req("Buyurtma berish (katalogdan)", "POST", "/client/orders",
            {"address_id": "{{address_id}}", "items": [{"product_id": "{{product_id}}", "quantity": 2}, {"name": "Non", "quantity": 4, "unit": "pcs"}]},
            code=201, desc="Katalog mahsuloti: `product_id` + `quantity` (nom va birlik mahsulotdan olinadi). Erkin matn bilan aralashtirish mumkin. Avval \"Mahsulotlar\" va \"Manzil qo'shish\" ni bajaring.",
            tests=[save('order_id', 'pm.response.json().data.id'),
                   "pm.test('do\'kon nomi nusxalandi', () => pm.expect(pm.response.json().data.items[0].shop_name).to.be.a('string'));"]),
        req("Buyurtma berish (saqlangan manzil)", "POST", "/client/orders",
            {"address_id": "{{address_id}}", "items": [{"name": "Olma", "quantity": 2, "unit": "kg"}]},
            code=201, desc="Avval \"Manzil qo'shish\" ni bajaring.", tests=[save('order_id', 'pm.response.json().data.id')]),
        req("Mening buyurtmalarim", "GET", "/client/orders",
            query=[("scope", "active", "active | history | (bo'sh — hammasi)"), ("limit", "20", "1..100")]),
        req("Buyurtma tafsiloti", "GET", "/client/orders/{{order_id}}",
            tests=["pm.test('tarix bor', () => pm.expect(pm.response.json().data.history).to.be.an('array'));"]),
        req("Buyurtmani bekor qilish", "POST", "/client/orders/{{order_id}}/cancel", {"reason": "Rejalar o'zgardi"},
            desc="Faqat kuryer bozorlikni boshlaguncha (`can_cancel: true`). Aks holda 422."),
    ]),
], token='client_token')

courier = folder("4. Kuryer ilovasi", [
    req("Ishga chiqdim (onlayn)", "PATCH", "/courier/online", {"is_online": True}),
    req("Joylashuvni yuborish", "POST", "/courier/location", {"lat": 40.9983, "lng": 71.6726}),
    req("Faol buyurtmalarim", "GET", "/courier/orders", query=[("scope", "active", "active | history")],
        desc="Birinchi buyurtma `courier_order_id` ga saqlanadi.",
        tests=["const o = pm.response.json().data[0]; if (o) pm.collectionVariables.set('courier_order_id', o.id);"]),
    req("Buyurtma tafsiloti", "GET", "/courier/orders/{{courier_order_id}}",
        desc="Birinchi mahsulot `item_id` ga saqlanadi.",
        tests=["const it = (pm.response.json().data.items || [])[0]; if (it) pm.collectionVariables.set('item_id', it.id);"]),
    req("Qabul qilish (bozorlikni boshlash)", "POST", "/courier/orders/{{courier_order_id}}/accept",
        tests=["pm.test('holat: shopping', () => pm.expect(pm.response.json().data.status.value).to.eql('shopping'));"]),
    req("Rad etish", "POST", "/courier/orders/{{courier_order_id}}/decline", {"reason": "Mashina buzildi"},
        desc="Faqat `assigned` holatida. Buyurtma moderatorga qaytadi."),
    req("Mahsulot — olindi", "PATCH", "/courier/orders/{{courier_order_id}}/items/{{item_id}}",
        {"status": "purchased", "price": 18000, "courier_note": None},
        desc="`price` — shu qator uchun to'langan jami summa. Har bir mahsulot uchun `item_id` ni almashtirib takrorlang."),
    req("Mahsulot — topilmadi", "PATCH", "/courier/orders/{{courier_order_id}}/items/{{item_id}}",
        {"status": "unavailable", "courier_note": "Do'konda tugagan"}),
    req("Bozorlikni tugatish (chek bilan)", "POST", "/courier/orders/{{courier_order_id}}/purchase-complete",
        form=[{"key": "receipt", "type": "file", "src": [], "description": "jpg/png/webp, ≤5 MB (ixtiyoriy)"}],
        desc="Hamma mahsulot belgilangan bo'lishi kerak. Javobdagi `amounts.total` — mijozdan olinadigan summa.",
        tests=["pm.test('holat: delivering', () => pm.expect(pm.response.json().data.status.value).to.eql('delivering'));"]),
    req("Yetkazildi", "POST", "/courier/orders/{{courier_order_id}}/deliver",
        tests=["pm.test('to\\'langan', () => pm.expect(pm.response.json().data.payment.status.value).to.eql('paid'));"]),
    req("Tarix", "GET", "/courier/orders", query=[("scope", "history", "")]),
], token='courier_token')

logout = folder("5. Chiqish", [
    req("Mijoz — chiqish", "POST", "/auth/logout", desc="Token bekor bo'ladi; qaytadan 1-papka."),
], token='client_token')

collection = {
    "info": {
        "_postman_id": uid('collection'),
        "name": "Delivery API v1",
        "description": (
            "Bozorlik va yetkazib berish — mijoz va kuryer ilovalari API.\n\n"
            "**Boshlash:**\n1. Environment tanlang (`Delivery — Local`).\n2. `1. Avtorizatsiya` papkasini ishga tushiring — `client_token` va `courier_token` avtomatik saqlanadi.\n"
            "3. `3. Mijoz ilovasi → Buyurtma berish`.\n4. Admin panelda (`/admin`, moderator `+998900000002` / `moderator123`) buyurtmani tasdiqlang va kuryerni tanlang.\n"
            "5. `4. Kuryer ilovasi` — qabul qilish → mahsulotlarni belgilash → bozorlikni tugatish → yetkazildi.\n\n"
            "Javob formati, xatolar va qoidalar: `docs/api.md`, Swagger: `/docs/api`."
        ),
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
    },
    "event": [script(["pm.test('JSON javob', () => pm.response.to.be.json);"])],
    "variable": [
        {"key": k, "value": v} for k, v in [
            ("client_token", ""), ("courier_token", ""), ("order_id", ""), ("courier_order_id", ""),
            ("item_id", ""), ("address_id", ""), ("shop_id", ""), ("product_id", ""), ("notification_id", ""), ("idempotency_key", ""),
        ]
    ],
    "item": [auth, common, client, courier, logout],
}
json.dump(collection, open(f"{OUT}/Delivery.postman_collection.json", "w"), ensure_ascii=False, indent=2)

def env(name, base):
    vals = [("base_url", base, "default"), ("client_phone", "+998935550001", "default"), ("otp_code", "1111", "default"),
            ("courier_phone", "+998901110001", "default"), ("courier_password", "courier123", "secret")]
    return {"id": uid('env' + name), "name": name, "_postman_variable_scope": "environment",
            "values": [{"key": k, "value": v, "type": t, "enabled": True} for k, v, t in vals]}

json.dump(env("Delivery — Local", "http://127.0.0.1:8000/api/v1"), open(f"{OUT}/Delivery.local.postman_environment.json", "w"), ensure_ascii=False, indent=2)
json.dump(env("Delivery — Test server", "https://delivery-5-9-0-240.sslip.io/api/v1"), open(f"{OUT}/Delivery.test.postman_environment.json", "w"), ensure_ascii=False, indent=2)

def walk(items):
    for i in items:
        if "item" in i: yield from walk(i["item"])
        else: yield i
reqs = list(walk(collection["item"]))
print(len(reqs), "requests")

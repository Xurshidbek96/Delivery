"""Postman collection'ni jonli serverda boshidan oxirigacha o'tkazadi (Newman o'rniga, faqat lokal).

Ishlatish (loyiha ildizidan, server ishlab turganda, demo seed bilan):
  python3 tools/postman/smoke_run.py docs/postman/Delivery.postman_collection.json \
      docs/postman/Delivery.local.postman_environment.json http://127.0.0.1:8000/api/v1 .
Moderator qadami (tasdiqlash + kuryer tanlash) admin panel o'rniga `php artisan tinker` orqali bajariladi.
OTP qayta yuborish cheklovi sababli ketma-ket ishga tushirishlar orasida 60 soniya kuting.
"""
import json, re, sys, subprocess, urllib.request, uuid
COL, ENV, BASE, APPDIR = sys.argv[1:5]
col = json.load(open(COL)); env = {v["key"]: v["value"] for v in json.load(open(ENV))["values"]}
env["base_url"] = BASE
vars = {v["key"]: v["value"] for v in col["variable"]}
def sub(s):
    s = s.replace("{{$guid}}", str(uuid.uuid4()))
    return re.sub(r"\{\{(\w+)\}\}", lambda m: str(vars.get(m.group(1)) or env.get(m.group(1), "")), s)
def walk(items, auth=None):
    for i in items:
        a = i.get("auth", auth)
        if "item" in i: yield from walk(i["item"], a)
        else: yield i, a
fails = 0
def run(item, auth):
    global fails
    r = item["request"]
    for ev in item.get("event", []):
        if ev["listen"] == "prerequest" and any("idempotency_key" in l for l in ev["script"]["exec"]):
            vars["idempotency_key"] = str(uuid.uuid4())
    url = sub(r["url"]["raw"]); data = None
    headers = {h["key"]: sub(h["value"]) for h in r["header"]}
    a = r.get("auth", auth)
    if a and a["type"] == "bearer": headers["Authorization"] = "Bearer " + sub(a["bearer"][0]["value"])
    body = r.get("body")
    if body and body["mode"] == "raw": data = sub(body["raw"]).encode()
    if body and body["mode"] == "formdata": data = b""; headers["Content-Type"] = "multipart/form-data; boundary=x"; data = b"--x--\r\n"
    req = urllib.request.Request(url, data=data, method=r["method"], headers=headers)
    try:
        resp = urllib.request.urlopen(req); code = resp.status; js = json.loads(resp.read() or b"{}")
    except urllib.error.HTTPError as e:
        code = e.code; js = json.loads(e.read() or b"{}")
    expected = int(re.search(r"status\((\d+)\)", item["event"][0]["script"]["exec"][0]).group(1))
    # tokenlar va ID'larni saqlash (collection test skriptlari bilan bir xil)
    for line in item["event"][0]["script"]["exec"]:
        m = re.search(r"set\('(\w+)', pm\.response\.json\(\)\.([\w.]+)\)", line)
        if m and code < 300:
            v = js
            for k in m.group(2).split("."): v = v[k]
            vars[m.group(1)] = v
    if "data[0]" in "".join(item["event"][0]["script"]["exec"]) and js.get("data"):
        key = "notification_id" if "notification" in url else "courier_order_id"
        vars[key] = js["data"][0]["id"]
    if "items || []" in "".join(item["event"][0]["script"]["exec"]):
        vars["item_id"] = js["data"]["items"][0]["id"]
    ok = code == expected
    fails += 0 if ok else 1
    print(("OK  " if ok else "FAIL"), code, r["method"], url.replace(BASE, ""), "" if ok else json.dumps(js, ensure_ascii=False)[:160])
    return js

items = list(walk(col["item"]))
by = {i["name"]: (i, a) for i, a in items}
order = [n for n, _ in [(i["name"], 0) for i, _ in items]]
skip_until_admin = {"Buyurtmani bekor qilish", "Rad etish", "Manzilni o'chirish", "Mahsulot — topilmadi", "Mijoz — chiqish"}
for name in order:
    if name in skip_until_admin or by[name][0]["request"]["url"]["raw"].find("/courier/") > -1 and name not in ("Kuryer — kirish (token)",):
        continue
    run(*by[name])
# Moderator qadami (admin panel o'rniga Action'lar)
php = f"""$o=App\\Models\\Order::findOrFail({vars['order_id']}); $m=App\\Models\\User::where('phone','+998900000002')->first(); $c=App\\Models\\User::where('phone','{env['courier_phone']}')->first();
app(App\\Actions\\Orders\\ConfirmOrder::class)->handle($o,$m); app(App\\Actions\\Orders\\AssignCourier::class)->handle($o,$c,$m); echo 'moderator: tasdiqlandi va kuryerga berildi';"""
print("--", subprocess.run(["php", "artisan", "tinker", "--execute", php], cwd=APPDIR, capture_output=True, text=True).stdout.strip())
for name in ["Ishga chiqdim (onlayn)", "Joylashuvni yuborish", "Faol buyurtmalarim", "Buyurtma tafsiloti", "Qabul qilish (bozorlikni boshlash)"]:
    pair = [(i, a) for i, a in items if i["name"] == name][-1]; run(*pair)
# hamma mahsulotni belgilash
detail = run(*[(i, a) for i, a in items if i["name"] == "Buyurtma tafsiloti"][-1])
its = detail["data"]["items"]
for k, it in enumerate(its):
    vars["item_id"] = it["id"]
    run(*by["Mahsulot — topilmadi" if (k == len(its) - 1 and len(its) > 1) else "Mahsulot — olindi"])
for name in ["Bozorlikni tugatish (chek bilan)", "Yetkazildi", "Tarix"]:
    run(*by[name])
# mijoz: tafsilot va yangi buyurtmani bekor qilish
client_detail = [(i, a) for i, a in items if i["name"] == "Buyurtma tafsiloti"][0]
run(*client_detail)
run(*by["Buyurtma berish (manzil bilan)"]); run(*by["Buyurtmani bekor qilish"]); run(*by["Manzilni o'chirish"]); run(*by["Mijoz — chiqish"])
print("FAILS:", fails)

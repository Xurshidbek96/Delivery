# Delivery — Texnik topshiriq (TZ)

| | |
|---|---|
| Versiya | 1.2 |
| Sana | 2026-11-20 |
| Holat | 1-bosqich (MVP) backend va admin panel tayyor, mobil ilovalar ishlab chiqilmoqda |
| Xizmat hududi | Namangan shahri |
| Tegishli hujjatlar | [docs/api.md](docs/api.md) — mobil API qo'llanmasi · `/docs/api` — Swagger · [docs/order-status-flow.md](docs/order-status-flow.md) · [docs/architecture.md](docs/architecture.md) |

Belgilar: ✅ — 1-bosqichda qilingan va ishlaydi · 🔜 — keyingi bosqich (arxitekturada joyi tayyor) · ❓ — buyurtmachi bilan kelishish kerak (17-bo'lim).

---

## 1. Loyiha haqida

**Delivery** — bozorlik va yetkazib berish xizmati. Mijoz telefonidan kerakli mahsulotlar ro'yxatini yuboradi, xizmat kuryeri bozor yoki do'kondan o'sha mahsulotlarni sotib olib, mijozning eshigigacha olib keladi. Mijoz mahsulot puli va yetkazish haqini kuryerga naqd to'laydi.

**Hudud:** xizmat **Namangan shahrida** ishlaydi.

**Muammo:** vaqti yo'q, keksa, kasal yoki yosh bolali odamlar uchun bozorga borish qiyin; mavjud marketplace'lar faqat o'z katalogidagi mahsulotlarni yetkazadi.

**Yechim:** mijoz istalgan mahsulotni erkin matn bilan yozadi ("2 kg kartoshka, yangi uzilgan") yoki xizmat katalogidan (do'konlar va ularning mahsulotlari) tanlaydi. Kuryer joyida narxni ko'radi, sotib oladi va har bir mahsulot narxini ilovaga kiritadi. Mijoz real vaqtda kuzatadi.

**Marketplace'dan farqi:** sotuvchi (vendor) akkaunti yo'q — do'konlar va mahsulotlarni xizmat xodimlari admin panelda yuritadi. Katalogdagi narx taxminiy; haqiqiy narxni kuryer kiritadi. Faqat to'rt ishtirokchi: mijoz, kuryer, moderator, administrator.

## 2. Biznes model

| Daromad manbai | Qanday | Holat |
|---|---|---|
| Yetkazish narxi | Har buyurtma uchun qat'iy summa (sozlamada, standart 15 000 so'm). Moderator tasdiqlashda o'zgartira oladi | ✅ |
| Xizmat haqi | Qo'shimcha qat'iy summa (standart 0) | ✅ |
| Masofa/zona bo'yicha narx | Manzil masofasiga qarab | 🔜 |

Mahsulot puli — mijozning o'z xaridi, xizmat daromadi emas. Kuryer bilan hisob-kitob modeli ❓ (17-bo'lim).

## 3. Ishtirokchilar va rollar

| Rol | Qayerda ishlaydi | Kim yaratadi | Kirish |
|---|---|---|---|
| **Mijoz** | Mijoz mobil ilovasi | O'zi (SMS kod orqali) | Telefon + SMS kod |
| **Kuryer** | Kuryer mobil ilovasi | Administrator yoki moderator (admin panel) | Telefon + parol |
| **Moderator** | Admin panel (web) | Administrator | Telefon + parol |
| **Administrator** | Admin panel (web) | Tizim (birinchi akkaunt) / boshqa administrator | Telefon + parol |

Bitta telefon raqami faqat bitta rolga tegishli.

### 3.1. Huquqlar matritsasi

| Amal | Mijoz | Kuryer | Moderator | Admin |
|---|:-:|:-:|:-:|:-:|
| Buyurtma berish, o'z buyurtmalarini ko'rish | ✅ | | | |
| O'z buyurtmasini bekor qilish (bozorlik boshlanguncha) | ✅ | | | |
| Biriktirilgan buyurtmani qabul qilish / rad etish | | ✅ | | |
| Mahsulot narxini kiritish, yetkazildi deb belgilash | | ✅ | | |
| Barcha buyurtmalarni ko'rish | | | ✅ | ✅ |
| Buyurtmani tasdiqlash, yetkazish narxini o'zgartirish | | | ✅ | ✅ |
| Kuryer tanlash va almashtirish | | | ✅ | ✅ |
| Istalgan ochiq buyurtmani bekor qilish | | | ✅ | ✅ |
| Kuryer qo'shish, tahrirlash, bloklash | | | ✅ | ✅ |
| Mijozni bloklash | | | ✅ | ✅ |
| Do'konlar va mahsulotlar (katalog) boshqaruvi | | | ✅ | ✅ |
| Katalogni ko'rish (faol do'kon va mahsulotlar) | ✅ | | | |
| Xodimlar (moderator/admin) boshqaruvi | | | | ✅ |
| Sozlamalar (narxlar, cheklovlar, qabulni yopish) | | | | ✅ |

## 4. Platformalar

| Platforma | Texnologiya | Kim qiladi | Holat |
|---|---|---|---|
| Backend + REST API | Laravel 12, PHP 8.2+, MySQL | Backend jamoa | ✅ |
| Admin panel | Web (shu backend ichida), kompyuter va telefon brauzeri | Backend jamoa | ✅ |
| Mijoz ilovasi | Android + iOS | Mobil jamoa | 🔜 API tayyor |
| Kuryer ilovasi | Android (iOS ixtiyoriy) | Mobil jamoa | 🔜 API tayyor |

Til: o'zbek (lotin). Valyuta: so'm. Vaqt zonasi: Asia/Tashkent.

## 5. Asosiy biznes jarayon

```
 MIJOZ                       MODERATOR                         KURYER
 ─────                       ─────────                         ──────
 1. Ro'yxat + manzil
    yuboradi ──────────────► 2. "Yangi" buyurtma (ovoz,
                                hisoblagich) — tekshiradi,
                                kerak bo'lsa qo'ng'iroq qiladi
                             3. Tasdiqlaydi ──────► (mijozga: "Tasdiqlandi")
                             4. Kuryerni tanlaydi ──────────────► 5. "Yangi buyurtma" xabari
                                                                  6. Qabul qiladi ──► (mijozga: "Kuryer bozorlikda")
                                                                     yoki rad etadi ──► 4-qadamga qaytadi
                                                                  7. Bozorda har mahsulotni
                                                                     "olindi + narx" yoki "topilmadi"
                                                                  8. Bozorlikni tugatadi (+ chek rasmi)
 (mijozga: "Yo'lda, jami: X so'm") ◄──────────────────────────────┘
                                                                  9. Topshiradi, naqd oladi,
 (mijozga: "Yetkazildi") ◄─────────────────────────────────────── "Yetkazildi" bosadi
```

## 6. Buyurtma holatlari ✅

| Holat (`value`) | Nomi | Ma'nosi | Keyingi holatlar |
|---|---|---|---|
| `pending` | Yangi | Mijoz yubordi, moderator ko'rmagan | `confirmed`, `cancelled` |
| `confirmed` | Tasdiqlandi | Moderator tasdiqladi, kuryer tanlanmagan | `assigned`, `cancelled` |
| `assigned` | Kuryerga berildi | Kuryer tanlandi, u qabul qilishi kutilmoqda | `shopping`, `confirmed` (kuryer rad etdi), `cancelled` |
| `shopping` | Bozorlik qilinmoqda | Kuryer qabul qildi va xarid qilmoqda | `delivering`, `cancelled` |
| `delivering` | Yo'lda | Xarid tugadi, kuryer mijozga ketmoqda | `delivered`, `cancelled` |
| `delivered` | Yetkazildi | Yakuniy | — |
| `cancelled` | Bekor qilindi | Yakuniy, sababi saqlanadi | — |

Har bir o'zgarish tarixga yoziladi: kim (rol va ism), qachon, izoh. Qadamlarni sakrab o'tib bo'lmaydi (masalan, `pending → shopping` taqiqlangan). Batafsil: [docs/order-status-flow.md](docs/order-status-flow.md).

## 7. Funksional talablar

### 7.1. Mijoz ilovasi

| # | Ekran / funksiya | Talab | API | Holat |
|---|---|---|---|---|
| M1 | Ishga tushish | Sozlamalarni yuklash; buyurtma qabul qilish yopiq bo'lsa — ogohlantirish va qo'llab-quvvatlash telefoni | `GET /meta` | ✅ API |
| M2 | Kirish | Telefon → 4 xonali SMS kod → kirish. Yangi foydalanuvchi ismini kiritadi. Kodni 60 soniyadan keyin qayta so'rash | `POST /auth/otp/send`, `/verify` | ✅ API |
| M3a | Katalog | Do'konlar ro'yxati (logotip, manzil, mahsulotlar soni), do'kon mahsulotlari, qidiruv. Mahsulot: rasm, nom, birlik, taxminiy narx ("≈ 8 000 so'm/kg"). "Ro'yxatga qo'shish" — miqdor bilan | `GET /client/shops`, `/client/products` | ✅ API |
| M3 | Yangi buyurtma — ro'yxat | Katalogdan tanlangan mahsulotlar va erkin matn bilan qo'shilganlar bitta ro'yxatda. Erkin matn: nom (majburiy), miqdor, birlik (dona, kg, gramm, litr, qadoq, bog'), izoh. Tahrirlash, o'chirish, tartib. Maksimal soni — sozlamada (standart 50) | — | 🔜 ilova |
| M4 | Manzil | Saqlangan manzilni tanlash yoki yangisini kiritish: manzil matni, xaritadan nuqta, podyezd, qavat, xonadon, mo'ljal. Qabul qiluvchi ismi/telefoni (standart — o'zi) | `GET/POST /client/addresses` | ✅ API |
| M5 | Tasdiqlash | Ro'yxat, manzil, yetkazish narxi, to'lov usuli (naqd), izoh. "Mahsulot narxi kuryer xarid qilgandan keyin ma'lum bo'ladi" degan eslatma | `POST /client/orders` | ✅ API |
| M6 | Buyurtmalarim | Faol va tarix tablari; raqam, holat, sana, summa | `GET /client/orders?scope=` | ✅ API |
| M7 | Buyurtma tafsiloti | Holat qadamlari, kuryer ismi va telefoni (biriktirilgandan keyin), har mahsulot holati va narxi, jami summa, chek rasmi, tarix | `GET /client/orders/{id}` | ✅ API |
| M8 | Bekor qilish | Faqat kuryer bozorlikni boshlaguncha; sabab ixtiyoriy | `POST /client/orders/{id}/cancel` | ✅ API |
| M9 | Bildirishnomalar | Ro'yxat, o'qilmaganlar soni, push | `GET /notifications`, `POST /devices` | ✅ API · push 🔜 |
| M10 | Profil | Ism, telefon, saqlangan manzillar, chiqish | `GET/PATCH /me`, `POST /auth/logout` | ✅ API |

### 7.2. Kuryer ilovasi

| # | Ekran / funksiya | Talab | API | Holat |
|---|---|---|---|---|
| K1 | Kirish | Admin bergan telefon va parol | `POST /auth/courier/login` | ✅ API |
| K2 | Ish holati | "Ishdaman / dam olyapman" tugmasi. Onlayn kuryerlar moderatorga birinchi ko'rinadi | `PATCH /courier/online` | ✅ API |
| K3 | Joylashuv | Onlayn paytda har 30–60 soniyada joylashuvni yuborish | `POST /courier/location` | ✅ API |
| K4 | Faol buyurtmalar | Biriktirilgan, bozorlikdagi va yo'ldagi buyurtmalar | `GET /courier/orders` | ✅ API |
| K5 | Buyurtma tafsiloti | Mijoz ismi va telefoni (qo'ng'iroq tugmasi), manzil (xaritada navigatsiya), ro'yxat, izohlar | `GET /courier/orders/{id}` | ✅ API |
| K6 | Qabul / rad | "Qabul qilish" — bozorlik boshlanadi. "Rad etish" — sabab bilan, buyurtma moderatorga qaytadi | `.../accept`, `.../decline` | ✅ API |
| K7 | Bozorlik | Ro'yxat chek-list sifatida: har mahsulotga "olindi" + to'langan summa yoki "topilmadi"; izoh ("o'rniga boshqa navi"). Joriy jami summa ko'rinib turadi | `PATCH .../items/{item}` | ✅ API |
| K8 | Bozorlikni tugatish | Chek rasmini olish (ixtiyoriy). Hamma mahsulot belgilanmaguncha tugma faol emas | `.../purchase-complete` | ✅ API |
| K9 | Topshirish | Mijozdan olinadigan jami summa katta shriftda; "Yetkazildi" | `.../deliver` | ✅ API |
| K10 | Tarix va bildirishnomalar | Yakunlangan buyurtmalar; "Yangi buyurtma" push'i | `GET /courier/orders?scope=history` | ✅ API |

### 7.3. Admin panel ✅

| # | Bo'lim | Talab |
|---|---|---|
| A1 | Kirish | Telefon + parol, "meni eslab qol". Faqat faol moderator/administrator kiradi |
| A2 | Bosh sahifa | Tasdiq kutayotganlar, jarayondagilar, bugun yetkazilganlar, bugungi daromad (yetkazish + xizmat haqi) va aylanma, onlayn kuryerlar; ochiq buyurtmalar ro'yxati; 7 kunlik grafik |
| A3 | Yangi buyurtma signali | Har 15 soniyada tekshiriladi: yangi buyurtma kelsa ovozli signal, xabar, yon panelda va brauzer sarlavhasida son |
| A4 | Buyurtmalar ro'yxati | Tablar: Barchasi / Yangi / Kuryer kutmoqda / Jarayonda / Yetkazilgan / Bekor qilingan (soni bilan). Qidiruv: raqam, telefon, manzil, ism. Filtr: kuryer, sana oralig'i |
| A5 | Buyurtma sahifasi | Holat qadamlari; mahsulotlar (kuryer narxlari va izohlari bilan); summalar; mijoz (buyurtmalari soni), manzil (xaritada), kuryer; mijoz izohi; chek rasmi; tarix |
| A6 | Tasdiqlash | Yetkazish narxini o'zgartirish va ichki izoh bilan |
| A7 | Kuryer tanlash | Ro'yxat tartibi: onlayn → bo'sh joyi bor → kamroq band → yaqinroq (km). Limitga yetgan kuryerni tanlab bo'lmaydi. `assigned` holatida — almashtirish |
| A8 | Bekor qilish | Istalgan ochiq holatda, sabab majburiy (mijozga yuboriladi) |
| A9 | Ichki izoh | Faqat xodimlar ko'radi, istalgan paytda |
| A10 | Kuryerlar | Ro'yxat (onlayn/oflayn/bloklangan, faol va yetkazgan buyurtmalar soni), qidiruv. Qo'shish/tahrirlash: ism, telefon (login), parol, transport, davlat raqami, izoh, faollik. Parol o'zgarsa yoki bloklansa — ilovadan chiqarib yuboriladi. Kuryer sahifasi: statistika, oxirgi joylashuv, buyurtmalari |
| A11 | Mijozlar | Ro'yxat (buyurtmalar soni, xarid summasi, oxirgi buyurtma), qidiruv. Mijoz sahifasi: buyurtmalari; bloklash/blokdan chiqarish |
| A11a | Do'konlar | Ro'yxat (faol/nofaol, faol/jami mahsulotlar soni), qidiruv. Qo'shish/tahrirlash: nom, manzil, telefon, koordinata, tavsif, logotip, tartib, faollik. Nofaol do'kon va uning barcha mahsulotlari mijozga ko'rinmaydi. Mahsuloti bor do'konni o'chirib bo'lmaydi (nofaol qilinadi). Do'kon sahifasida — uning mahsulotlari |
| A11b | Mahsulotlar | Har mahsulot bitta do'konga tegishli. Ro'yxat: do'kon, holat bo'yicha filtr, qidiruv. Qo'shish/tahrirlash: do'kon, nom, birlik, taxminiy narx, tavsif, rasm, tartib, faollik; ro'yxatdan bir bosishda faol/nofaol. O'chirilgan mahsulot eski buyurtmalarda nomi bilan qoladi |
| A12 | Xodimlar (admin) | Moderator/administrator qo'shish, rol, parol, bloklash. O'zini bloklab yoki admin huquqidan mahrum qilib bo'lmaydi |
| A13 | Sozlamalar (admin) | 11-bo'lim |
| A14 | Profil | Parolni almashtirish |
| A15 | Ko'rinish | Tungi rejim, telefon/planshetga moslashgan |

## 8. Biznes qoidalar ✅

1. **Narxni faqat server belgilaydi.** Mijoz ilovasi narx yubormaydi; yuborsa ham e'tiborsiz qoladi. Yetkazish va xizmat haqi buyurtma yaratilgan paytdagi sozlamadan olinadi.
2. **Jami summa** = kuryer kiritgan "olindi" mahsulotlar narxi + yetkazish narxi + xizmat haqi. "Topilmadi" mahsulotlar summaga kirmaydi.
3. Kuryer narxni **qator uchun jami** kiritadi (3 kg kartoshka uchun to'langan summa, 1 kg narxi emas).
4. **Bozorlikni tugatish** uchun hamma mahsulot belgilangan va kamida bittasi olingan bo'lishi shart. Hammasi topilmasa — kuryer moderatorga qo'ng'iroq qiladi, moderator bekor qiladi.
5. **Mijoz bekor qilishi** faqat `pending`, `confirmed`, `assigned` holatlarida. Kuryer bozorlikni boshlagandan keyin faqat moderator bekor qila oladi.
6. **Kuryer limiti:** bir vaqtda faol buyurtmalar soni sozlamadan oshmaydi (standart 3).
7. Bloklangan kuryer yoki mijoz tizimga kira olmaydi, faol tokenlari bekor qilinadi; bloklangan kuryerga buyurtma berib bo'lmaydi.
8. **Buyurtma qabul qilish yopiq** bo'lsa (sozlama), yangi buyurtma qabul qilinmaydi.
9. Buyurtmadagi manzil **nusxa** sifatida saqlanadi — mijoz manzilini keyin o'zgartirsa, eski buyurtmalar o'zgarmaydi.
10. Buyurtma raqami: `D` + 6 xonali tartib raqami (`D000123`).
11. Takroriy yuborish himoyasi: ilova `Idempotency-Key` yuborsa, internet uzilib qayta yuborilgan so'rov ikkinchi buyurtma yaratmaydi (24 soat).
12. Telefon raqamlari `+998XXXXXXXXX` ko'rinishiga keltiriladi; boshqa davlat raqamlari qabul qilinmaydi.
13. **Xizmat hududi — Namangan shahri.** Hududdan tashqari manzil hozircha tizimda avtomatik tekshirilmaydi: moderator tasdiqlashda ko'rib, kerak bo'lsa bekor qiladi (avtomatik chegara — 🔜 2-bosqich).
14. **Mijoz yetkazish paytida eshikda bo'lishi shart** va to'lovni kuryerga **naqd** beradi (hozircha yagona to'lov usuli). Ilova buyurtma berishdan oldin buni mijozga ko'rsatishi kerak.
15. **Katalog:** mahsulot bitta do'konga tegishli. Mijozga faqat **faol** mahsulot ko'rinadi va uning do'koni ham **faol** bo'lishi shart. Nofaol mahsulotni buyurtmaga qo'shib bo'lmaydi (422).
16. Katalogdagi narx — **taxminiy** (mo'ljal uchun). Buyurtma summasiga faqat kuryer kiritgan haqiqiy narx kiradi (2-qoida).
17. Katalogdan tanlangan mahsulot buyurtmaga **nusxa** sifatida yoziladi (nom, birlik, do'kon nomi): mahsulot keyin o'zgarsa yoki o'chirilsa, eski buyurtma o'zgarmaydi.

## 9. Bildirishnomalar ✅ (ilova ichida) · 🔜 (push)

| Hodisa | Kimga | Sarlavha | Matn (namuna) |
|---|---|---|---|
| Tasdiqlandi | Mijoz | Buyurtma tasdiqlandi | D000123 buyurtmangiz qabul qilindi. Tez orada kuryer biriktiriladi. |
| Kuryer biriktirildi | Kuryer | Yangi buyurtma | D000123 sizga biriktirildi: Uychi ko'chasi, 15-uy... |
| Bozorlik boshlandi | Mijoz | Kuryer bozorlikda | D000123: kuryer mahsulotlaringizni xarid qilmoqda. |
| Yo'lga chiqdi | Mijoz | Buyurtma yo'lda | D000123: kuryer sizga yo'l oldi. Jami: 119 000 so'm. |
| Yetkazildi | Mijoz | Yetkazildi | D000123 buyurtmangiz yetkazildi. Xaridingiz uchun rahmat! |
| Bekor qilindi | Mijoz; kuryer (agar u ishlayotgan bo'lsa) | Buyurtma bekor qilindi | D000123: <sabab> |

Amalni o'zi bajargan odamga xabar ketmaydi. Admin panelga yangi buyurtma signali — 7.3, A3.

## 10. To'lov

| Usul | Holat |
|---|---|
| Naqd — kuryerga, yetkazishda (mijoz eshikda kutib oladi) | ✅ Hozircha yagona usul. "Yetkazildi" bosilganda buyurtma "to'langan" bo'ladi |
| Click, Payme (oldindan yoki keyin) | 🔜 Ma'lumotlar modelida va kodda joyi tayyor; ulanish uchun merchant shartnomasi kerak |
| Pulni qaytarish | 🔜 Onlayn to'lov bilan birga |

## 11. Sozlamalar (admin panel) ✅

| Sozlama | Standart | Ta'siri |
|---|---|---|
| Buyurtma qabul qilish | Yoqilgan | O'chirilsa, mijoz ilovasi buyurtma bera olmaydi |
| Yetkazish narxi | 15 000 so'm | Yangi buyurtmalarga |
| Xizmat haqi | 0 so'm | Yangi buyurtmalarga |
| Kuryerda bir vaqtda buyurtma | 3 | Kuryer tanlashda limit |
| Buyurtmadagi mahsulotlar soni | 50 | Mijoz ilovasida va serverda |
| Qo'llab-quvvatlash telefoni | +998 90 000 00 00 | Ilovalarda ko'rsatiladi |

## 12. Nofunksional talablar

| Soha | Talab | Holat |
|---|---|---|
| Xavfsizlik | HTTPS majburiy; tokenlar (Sanctum); rollar bo'yicha qat'iy ajratish; har kim faqat o'z ma'lumotini ko'radi (boshqasiniki — "topilmadi") | ✅ |
| Xavfsizlik | SMS kod: hash'langan, 5 daqiqa amal qiladi, 5 urinish, 60 soniyada bir marta, IP va raqam bo'yicha cheklov | ✅ |
| Xavfsizlik | Login urinishlari cheklangan (10/daqiqa); fayl yuklash faqat rasm, ≤ 5 MB | ✅ |
| Ishonchlilik | Parallel amallar (ikki moderator bir buyurtmani tanlasa) bir-birini buzmaydi — qatorni bloklash | ✅ |
| Kuzatuv | Har bir holat o'zgarishi tarixi (kim, qachon, izoh) | ✅ |
| Ishlash | API javobi odatiy so'rovlarda < 300 ms; ro'yxatlar sahifalangan (≤ 100) | ✅ |
| Sifat | Avtomatik testlar (57 ta, SQLite va MySQL), hujjat–kod sinxronligi testi | ✅ |
| Zaxira nusxa | Kunlik baza nusxasi | 🔜 server tanlangach |
| Monitoring | Xatolar logi; kerak bo'lsa Sentry | 🔜 |

## 13. Tashqi integratsiyalar

| Integratsiya | Vazifasi | Holat |
|---|---|---|
| SMS (Eskiz yoki boshqa) | Kirish kodi | 🔜 Hozir test rejimi: kod logga yoziladi, `1111` |
| Firebase Cloud Messaging | Push | 🔜 Qurilma tokenlari allaqachon saqlanadi |
| Xarita (Yandex/Google) | Admin panelda manzil havolasi; ilovada nuqta tanlash va navigatsiya | ✅ havola · 🔜 ilovada |
| Click / Payme | Onlayn to'lov | 🔜 |

## 14. Texnik arxitektura (qisqa)

Bitta Laravel ilovasi: mobil API (`/api/v1`) va admin panel (`/admin`) bir xil biznes qatlamni ishlatadi. Har bir amal (tasdiqlash, kuryer tanlash, qabul qilish...) — alohida klass; holatni faqat bitta servis o'zgartiradi. Kelajakdagi o'zgarishlar (to'lov tizimi, masofaga ko'ra narx, avtomatik kuryer tanlash, SMS va push provayderi) mavjud kodni buzmasdan alohida drayver sifatida qo'shiladi. Batafsil: [docs/architecture.md](docs/architecture.md).

**Asosiy jadvallar:** `users` (barcha rollar), `courier_profiles` (transport, onlayn, joylashuv), `addresses`, `shops`, `products` (katalog), `orders`, `order_items` (`product_id` — katalogdan bo'lsa), `order_status_histories`, `otp_codes`, `settings`, `device_tokens`, `notifications`.

## 15. Bosqichlar

| Bosqich | Tarkibi | Holat |
|---|---|---|
| **1. MVP (test rejimi)** | Backend, mijoz va kuryer API, admin panel, qo'lda kuryer tanlash, naqd to'lov, ilova ichidagi bildirishnomalar, API hujjati (Swagger, Postman) | ✅ |
| **1.1. Ishga tushirish** | Server, domen, HTTPS; SMS provayder; mobil ilovalar (mijoz, kuryer); test buyurtmalar; `OTP_FIXED_CODE` ni o'chirish | 🔜 |
| **2. Avtomatlashtirish** | Push (FCM); eng yaqin bo'sh kuryerni avtomatik tanlash (moderator tasdiqlagandan keyin, rad etilsa keyingisiga); masofa/zona bo'yicha yetkazish narxi; xizmat hududi chegarasi | 🔜 |
| **3. Moliya va sifat** | Click/Payme; kuryer daromadi va hisob-kitobi; hisobotlar (kun/oy, kuryer bo'yicha); mijoz baholashi; real vaqt (WebSocket) | 🔜 |

## 16. Qabul qilish mezonlari (1-bosqich)

- [x] Mijoz SMS kod bilan kiradi, ro'yxat va manzil bilan buyurtma beradi; buyurtma admin panelda darhol "Yangi" bo'lib, ovozli signal bilan chiqadi.
- [x] Moderator buyurtmani tasdiqlaydi (yetkazish narxini o'zgartirib), kuryerni tanlaydi; mijoz va kuryerga bildirishnoma boradi.
- [x] Kuryer qabul qiladi yoki rad etadi; rad etilgan buyurtma moderatorga qaytadi.
- [x] Kuryer har mahsulot narxini kiritadi, jami summa avtomatik hisoblanadi, chek rasmi yuklanadi, "Yetkazildi" bilan buyurtma yopiladi va to'langan bo'ladi.
- [x] Mijoz bozorlik boshlanguncha bekor qila oladi; moderator istalgan paytda sabab bilan bekor qiladi.
- [x] Mijoz boshqa mijozning, kuryer boshqa kuryerning buyurtmasini ko'ra olmaydi.
- [x] Moderator sozlamalar va xodimlar bo'limiga kira olmaydi.
- [x] Moderator do'kon va mahsulot qo'shadi; mijoz faqat faol do'konlarning faol mahsulotlarini ko'radi va ulardan buyurtma bera oladi.
- [x] Barcha avtomatik testlar ikkala bazada o'tadi.
- [ ] Mobil ilovalar API'ga ulangan va to'liq oqim haqiqiy telefonlarda sinovdan o'tgan.
- [ ] Haqiqiy SMS provayder ulangan, test kodi o'chirilgan.

## 17. Ochiq savollar (buyurtmachi bilan kelishish kerak)

| # | Savol | Hozirgi taxmin |
|---|---|---|
| 1 | Kuryerga qanday haq to'lanadi: har buyurtma uchun qat'iy, yetkazish narxining foizi yoki oylik? Kuryer naqd pulni qachon va qanday topshiradi? | **Buyurtmachi hali hal qilmagan** (2026-11-20). Tizimda hisobga olinmaydi (yetkazish narxi to'liq xizmat daromadi sifatida ko'rsatiladi) |
| 2 | ~~Xizmat hududi~~ — **Namangan shahri** (hal qilindi). Hududdan tashqari buyurtma avtomatik rad etilsinmi, Namangan tumanlari kiradimi? | Moderator qo'lda bekor qiladi |
| 3 | Ish vaqti: buyurtmalar qachon qabul qilinadi? Avtomatik yopilsinmi? | Admin sozlamadan qo'lda yopadi |
| 4 | Minimal buyurtma summasi yoki mahsulotlar soni kerakmi? | Yo'q |
| 5 | Katta xarid uchun oldindan to'lov kerakmi (kuryer o'z pulidan sotib oladi)? Maksimal xarid summasi? | Cheklov yo'q; moderator qo'ng'iroq qilib aniqlaydi |
| 6 | Mahsulot topilmasa, o'xshashi bilan almashtirishga mijoz roziligi qanday olinadi (qo'ng'iroq, ilovada tasdiqlash)? | Kuryer qo'ng'iroq qiladi va izohga yozadi |
| 7 | Mijoz kuryer kiritgan narxlarni tasdiqlashi kerakmi? | Yo'q, chek rasmi yetarli |
| 8 | Mijoz eshikda bo'lishi shart, naqd to'laydi (hal qilindi, 8-bo'lim 14-qoida). Lekin baribir bo'lmasa yoki qabul qilmasa — xarid puli kim hisobidan, kuryer qancha kutadi? | Moderator bekor qiladi, jarima qoidasi kelishilmagan |
| 9 | Ilova nomi, logotip va brend ranglari | "Delivery", vaqtinchalik binafsha rang |
| 10 | Katalog mahsulotini kuryer **aynan o'sha do'kondan** olishi shartmi yoki boshqa joydan ham bo'ladimi? | Do'kon nomi kuryerga ko'rinadi, majburiy emas |
| 11 | Mahsulot kategoriyalari (sabzavot, sut mahsulotlari...) kerakmi? Katalog narxi qat'iy bo'lishi kerakmi (kuryer kiritmasin)? | Kategoriya yo'q; narx taxminiy, kuryer kiritadi |

## 18. Atamalar

| Atama | Ma'nosi |
|---|---|
| Buyurtma | Mijozning bitta yetkazish so'rovi: mahsulotlar ro'yxati + manzil |
| Mahsulot (pozitsiya) | Ro'yxatdagi bitta qator: nom, miqdor, birlik, izoh |
| Xarid summasi (`items_total`) | Kuryer to'lagan mahsulot narxlari yig'indisi |
| Jami (`total`) | Mijozdan olinadigan summa: xarid + yetkazish + xizmat haqi |
| Faol buyurtma (kuryer) | `assigned`, `shopping` yoki `delivering` holatidagi buyurtma |
| Ochiq buyurtma | Yakunlanmagan (`delivered`/`cancelled` emas) buyurtma |
| Onlayn kuryer | Ilovada "Ishdaman" holatini yoqqan kuryer |

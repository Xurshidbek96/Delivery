---
name: admin-page
description: Delivery admin panelida (/admin, Blade) sahifa, forma, jadval, filtr, amal yoki menyu qo'shish/o'zgartirish, dizaynni (ranglar, tungi rejim, komponentlar) sozlash va moderator/admin ruxsatlari. Ishlating, agar foydalanuvchi "admin panel", "panel", "sahifa", "dashboard", "jadval", "filtr", "forma", "menyu", "dizayn", "rang", "moderator ko'rsin", "admin ko'rsin", "hisobot" desa.
---

# Admin sahifa

## Tuzilma

| Qism | Joy |
|---|---|
| Route | `routes/web.php` → `admin.` guruhi. Moderator+admin: `role:moderator,admin`; faqat admin: ichki `role:admin` guruh |
| Controller | `app/Http/Controllers/Admin/` — yupqa; amal → `Actions\*`, `back()->with('success', '...')` |
| Validatsiya | `app/Http/Requests/Admin/`; checkbox → `prepareForValidation` da `$this->boolean()` |
| View | `resources/views/admin/<bo'lim>/`, `@extends('layouts.admin')`, `title` view'ga uzatiladi |
| Komponentlar | `<x-field name label hint>`, `<x-status-badge :status>`, `<x-empty icon title>`, `admin.partials.orders-table`, `admin.partials.pagination` (avtomatik) |
| Dizayn | `public/css/admin.css` — rang faqat `:root`/`html.dark` token'lari orqali (`--brand`, `--success-soft`...). Inline rang yozmang |
| JS | `public/js/admin.js` — `data-confirm`, `data-modal-open="id"`, `tr[data-href]`, `data-theme-toggle`. Kutubxona qo'shmang |
| Menyu | `resources/views/layouts/admin.blade.php` (`@class(['active' => request()->routeIs(...)])`, ikon — Tabler `ti-*`) |

## Qoidalar

- Foydalanuvchi route parametri (`{courier}`, `{client}`, `{staff}`) — `AppServiceProvider` `Route::bind` rolga bog'langan; yangi rolli parametr bo'lsa shu yerga qo'shing.
- View'dagi har bir relation — controllerda `with()` (lokalda lazy loading xato beradi).
- Jadval: `.card.card-flush > .table-wrap > table.table`; ustun soni ko'p bo'lsa sahifaning tor joyiga qo'ymang.
- Raqam/pul: `App\Support\Money::format()`, telefon: `Phone::format()`, sana: `UzDate::long()` yoki `format('d.m.Y H:i')` (Carbon `uz` lokali kirillcha — `translatedFormat` ishlatmang).
- Xavfli amal — POST/PUT forma + `@csrf` + `data-confirm`; GET bilan o'zgartirmang.
- Telefon kengligi (390px) va tungi rejimni tekshiring.

## Test

`tests/Feature/Admin/AdminPanelTest::test_all_pages_render_for_admin` ga URL qo'shing; amal uchun — muvaffaqiyat, moderator ruxsati (admin-only bo'lsa 403), validatsiya.

Tugatgach — `verify`.

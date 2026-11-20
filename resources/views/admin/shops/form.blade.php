@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.shops.index') }}">Do'konlar</a><i class="ti ti-chevron-right"></i>{{ $shop ? $shop->name : 'Yangi' }}</div>
            <h1 class="page-title">{{ $title }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $shop ? route('admin.shops.update', $shop) : route('admin.shops.store') }}" enctype="multipart/form-data" class="card" style="max-width:820px">
        @csrf
        @if($shop) @method('PUT') @endif
        <div class="card-body form-grid">
            <div class="form-grid cols-2">
                <x-field name="name" label="Nomi">
                    <input class="input" id="name" name="name" value="{{ old('name', $shop?->name) }}" required maxlength="120">
                </x-field>
                <x-field name="phone" label="Telefon" hint="Ixtiyoriy">
                    <input class="input" id="phone" name="phone" type="tel" value="{{ old('phone', $shop?->phone) }}" placeholder="+998">
                </x-field>
            </div>
            <x-field name="address" label="Manzil" hint="Kuryer qayerdan xarid qilishini bilishi uchun">
                <input class="input" id="address" name="address" value="{{ old('address', $shop?->address) }}" placeholder="Chorsu bozori, 3-qator">
            </x-field>
            <div class="form-grid cols-2">
                <x-field name="lat" label="Kenglik (lat)" hint="Ixtiyoriy, masalan 40.9983">
                    <input class="input" id="lat" name="lat" inputmode="decimal" value="{{ old('lat', $shop?->lat) }}">
                </x-field>
                <x-field name="lng" label="Uzunlik (lng)" hint="Ixtiyoriy, masalan 71.6726">
                    <input class="input" id="lng" name="lng" inputmode="decimal" value="{{ old('lng', $shop?->lng) }}">
                </x-field>
            </div>
            <x-field name="description" label="Tavsif" hint="Mijozga ko'rinadi">
                <textarea class="textarea" id="description" name="description" rows="2" maxlength="1000">{{ old('description', $shop?->description) }}</textarea>
            </x-field>
            <div class="form-grid cols-2">
                <x-field name="image" label="Logotip yoki rasm" hint="JPG, PNG, WEBP, 5 MB gacha">
                    <div class="row">
                        @include('admin.partials.thumb', ['model' => $shop, 'icon' => 'ti-building-store'])
                        <input class="input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                    </div>
                    @if($shop?->image_path)
                        <label class="check"><input type="checkbox" name="remove_image" value="1"> Rasmni o'chirish</label>
                    @endif
                </x-field>
                <x-field name="sort_order" label="Tartib" hint="Kichik raqam ro'yxatda oldinroq">
                    <input class="input" id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $shop?->sort_order ?? 0) }}">
                </x-field>
            </div>
            <label class="switch">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shop?->is_active ?? true))>
                <span class="track"></span> Faol (o'chirilsa — do'kon va barcha mahsulotlari mijozga ko'rinmaydi)
            </label>
        </div>
        <div class="card-footer form-actions">
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> Saqlash</button>
            <a href="{{ $shop ? route('admin.shops.show', $shop) : route('admin.shops.index') }}" class="btn btn-ghost">Bekor qilish</a>
        </div>
    </form>

    @if($shop)
        <form method="POST" action="{{ route('admin.shops.destroy', $shop) }}" class="row" style="max-width:820px" data-confirm="«{{ $shop->name }}» do'konini o'chirasizmi?">
            @csrf @method('DELETE')
            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> Do'konni o'chirish</button>
            <span class="subtle">Faqat mahsulotsiz do'konni o'chirish mumkin.</span>
        </form>
    @endif
@endsection

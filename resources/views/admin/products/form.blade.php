@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <a href="{{ route('admin.products.index') }}">Mahsulotlar</a><i class="ti ti-chevron-right"></i>{{ $product ? $product->name : 'Yangi' }}
            </div>
            <h1 class="page-title">{{ $title }}</h1>
        </div>
    </div>

    @if($shops->isEmpty())
        <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i><div>Avval <a class="link" href="{{ route('admin.shops.create') }}">do'kon qo'shing</a> — mahsulot do'konga tegishli bo'ladi.</div></div>
    @endif

    <form method="POST" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="card" style="max-width:820px">
        @csrf
        @if($product) @method('PUT') @endif
        <div class="card-body form-grid">
            <x-field name="shop_id" label="Do'kon">
                <select class="select" id="shop_id" name="shop_id" required>
                    <option value="">— tanlang —</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" @selected((int) old('shop_id', $shopId) === $s->id)>{{ $s->name }}{{ $s->is_active ? '' : ' (nofaol)' }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field name="name" label="Nomi">
                <input class="input" id="name" name="name" value="{{ old('name', $product?->name) }}" required maxlength="150" placeholder="Kartoshka">
            </x-field>
            <div class="form-grid cols-2">
                <x-field name="unit" label="O'lchov birligi">
                    <select class="select" id="unit" name="unit">
                        @foreach($units as $u)
                            <option value="{{ $u->value }}" @selected(old('unit', $product?->unit->value ?? 'kg') === $u->value)>{{ $u->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="price" label="Taxminiy narx (1 birlik, so'm)" hint="Ixtiyoriy. Mijozga mo'ljal; haqiqiy narxni kuryer xariddan keyin kiritadi">
                    <input class="input" id="price" name="price" inputmode="numeric" value="{{ old('price', $product?->price) }}" placeholder="8000">
                </x-field>
            </div>
            <x-field name="description" label="Tavsif" hint="Ixtiyoriy, mijozga ko'rinadi">
                <textarea class="textarea" id="description" name="description" rows="2" maxlength="1000">{{ old('description', $product?->description) }}</textarea>
            </x-field>
            <div class="form-grid cols-2">
                <x-field name="image" label="Rasm" hint="JPG, PNG, WEBP, 5 MB gacha">
                    <div class="row">
                        @include('admin.partials.thumb', ['model' => $product, 'icon' => 'ti-package'])
                        <input class="input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                    </div>
                    @if($product?->image_path)
                        <label class="check"><input type="checkbox" name="remove_image" value="1"> Rasmni o'chirish</label>
                    @endif
                </x-field>
                <x-field name="sort_order" label="Tartib" hint="Kichik raqam ro'yxatda oldinroq">
                    <input class="input" id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $product?->sort_order ?? 0) }}">
                </x-field>
            </div>
            <label class="switch">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true))>
                <span class="track"></span> Faol (mijoz ilovasida ko'rinadi)
            </label>
        </div>
        <div class="card-footer form-actions">
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> Saqlash</button>
            <a href="{{ $shopId ? route('admin.shops.show', $shopId) : route('admin.products.index') }}" class="btn btn-ghost">Bekor qilish</a>
        </div>
    </form>

    @if($product)
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="row" style="max-width:820px" data-confirm="«{{ $product->name }}» mahsulotini o'chirasizmi?">
            @csrf @method('DELETE')
            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> Mahsulotni o'chirish</button>
            <span class="subtle">Eski buyurtmalarda mahsulot nomi saqlanib qoladi.</span>
        </form>
    @endif
@endsection

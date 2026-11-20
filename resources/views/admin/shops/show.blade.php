@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.shops.index') }}">Do'konlar</a><i class="ti ti-chevron-right"></i>{{ $shop->name }}</div>
            <div class="row">
                @include('admin.partials.thumb', ['model' => $shop, 'icon' => 'ti-building-store', 'size' => 'lg'])
                <div>
                    <h1 class="page-title">{{ $shop->name }}</h1>
                    <p class="page-subtitle">
                        @if($shop->is_active)<span class="text-success">Faol — mijozga ko'rinadi</span>@else<span class="text-danger">Nofaol — mijozga ko'rinmaydi</span>@endif
                        @if($shop->address) · {{ $shop->address }}@endif
                        @if($shop->phone) · <a class="link" href="tel:{{ $shop->phone }}">{{ \App\Support\Phone::format($shop->phone) }}</a>@endif
                        @if($shop->lat !== null) · <a class="link" target="_blank" rel="noopener" href="https://yandex.uz/maps/?pt={{ $shop->lng }},{{ $shop->lat }}&z=17&l=map">xaritada</a>@endif
                    </p>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <form method="POST" action="{{ route('admin.shops.toggle', $shop) }}" @if($shop->is_active) data-confirm="Do'kon va uning barcha mahsulotlari mijozlardan yashiriladi. Davom etasizmi?" @endif>
                @csrf
                <button class="btn btn-outline" type="submit">
                    <i class="ti {{ $shop->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i> {{ $shop->is_active ? 'Nofaol qilish' : 'Faollashtirish' }}
                </button>
            </form>
            <a href="{{ route('admin.shops.edit', $shop) }}" class="btn btn-outline"><i class="ti ti-pencil"></i> Tahrirlash</a>
            <a href="{{ route('admin.products.create', ['shop_id' => $shop->id]) }}" class="btn btn-primary"><i class="ti ti-plus"></i> Mahsulot qo'shish</a>
        </div>
    </div>

    @if($shop->description)
        <div class="alert alert-info"><i class="ti ti-info-circle"></i><div>{{ $shop->description }}</div></div>
    @endif

    <section class="card card-flush">
        <div class="card-header"><h2 class="card-title"><i class="ti ti-package"></i> Mahsulotlar</h2><span class="subtle">{{ $products->total() }} ta</span></div>
        @include('admin.products.table', ['products' => $products, 'showShop' => false])
    </section>
    {{ $products->links() }}
@endsection

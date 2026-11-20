@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Mahsulotlar</h1>
            <p class="page-subtitle">Mahsulot do'konga tegishli. Mijozga mahsulot ham, do'kon ham faol bo'lsa ko'rinadi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.products.create', array_filter(['shop_id' => request('shop_id')])) }}" class="btn btn-primary"><i class="ti ti-plus"></i> Mahsulot qo'shish</a>
        </div>
    </div>

    <form method="GET" class="card">
        <div class="card-body toolbar">
            <div class="field grow">
                <label class="label" for="q">Qidiruv</label>
                <input class="input" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Mahsulot nomi">
            </div>
            <div class="field">
                <label class="label" for="shop_id">Do'kon</label>
                <select class="select" id="shop_id" name="shop_id" onchange="this.form.submit()">
                    <option value="">Barchasi</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" @selected((int) request('shop_id') === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="state">Holat</label>
                <select class="select" id="state" name="state" onchange="this.form.submit()">
                    <option value="">Barchasi</option>
                    <option value="visible" @selected(request('state') === 'visible')>Mijozga ko'rinadi</option>
                    <option value="inactive" @selected(request('state') === 'inactive')>Nofaol</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i> Qidirish</button>
        </div>
    </form>

    <section class="card card-flush">
        @include('admin.products.table', ['products' => $products, 'showShop' => true])
    </section>

    {{ $products->links() }}
@endsection

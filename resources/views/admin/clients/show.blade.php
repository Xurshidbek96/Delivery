@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.clients.index') }}">Mijozlar</a><i class="ti ti-chevron-right"></i>{{ $client->name }}</div>
            <div class="row">
                <span class="avatar avatar-lg">{{ $client->initials() }}</span>
                <div>
                    <h1 class="page-title">{{ $client->name }}</h1>
                    <p class="page-subtitle"><a class="link" href="tel:{{ $client->phone }}">{{ \App\Support\Phone::format($client->phone) }}</a> · {{ $client->created_at->format('d.m.Y') }} dan beri</p>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <form method="POST" action="{{ route('admin.clients.toggle', $client) }}" data-confirm="{{ $client->is_active ? 'Mijozni bloklaysizmi? U ilovadan chiqarib yuboriladi.' : 'Mijozni blokdan chiqarasizmi?' }}">
                @csrf
                @if($client->is_active)
                    <button class="btn btn-danger" type="submit"><i class="ti ti-lock"></i> Bloklash</button>
                @else
                    <button class="btn btn-outline" type="submit"><i class="ti ti-lock-open"></i> Blokdan chiqarish</button>
                @endif
            </form>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><div class="stat-top"><span class="stat-label">Buyurtmalar</span><span class="stat-icon"><i class="ti ti-receipt"></i></span></div><div class="stat-value">{{ $orders->total() }}</div></div>
        <div class="stat tone-success"><div class="stat-top"><span class="stat-label">Xarid summasi</span><span class="stat-icon"><i class="ti ti-cash"></i></span></div><div class="stat-value">{{ \App\Support\Money::format($spent, false) }}</div><div class="stat-hint">so'm, yetkazilganlar</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Saqlangan manzillar</span><span class="stat-icon"><i class="ti ti-map-pin"></i></span></div><div class="stat-value">{{ $client->addresses->count() }}</div></div>
    </div>

    <section class="card card-flush">
        <div class="card-header"><h2 class="card-title"><i class="ti ti-receipt"></i> Buyurtmalari</h2></div>
        @include('admin.partials.orders-table', ['orders' => $orders, 'showClient' => false])
    </section>
    {{ $orders->links() }}
@endsection

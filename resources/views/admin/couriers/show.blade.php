@extends('layouts.admin')

@section('content')
    @php($p = $courier->courierProfile)
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.couriers.index') }}">Kuryerlar</a><i class="ti ti-chevron-right"></i>{{ $courier->name }}</div>
            <div class="row">
                <span class="avatar avatar-lg">{{ $courier->initials() }}</span>
                <div>
                    <h1 class="page-title">{{ $courier->name }}</h1>
                    <p class="page-subtitle">
                        <a class="link" href="tel:{{ $courier->phone }}">{{ \App\Support\Phone::format($courier->phone) }}</a>
                        · {{ $p?->vehicle->label() }} {{ $p?->vehicle_number }}
                        · @if(! $courier->is_active)<span class="text-danger">Bloklangan</span>@elseif($p?->is_online)<span class="text-success">Onlayn</span>@else Oflayn @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.couriers.edit', $courier) }}" class="btn btn-outline"><i class="ti ti-pencil"></i> Tahrirlash</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat tone-info"><div class="stat-top"><span class="stat-label">Hozir faol</span><span class="stat-icon"><i class="ti ti-progress"></i></span></div><div class="stat-value">{{ $stats['active'] }}</div></div>
        <div class="stat tone-success"><div class="stat-top"><span class="stat-label">Bugun yetkazdi</span><span class="stat-icon"><i class="ti ti-package"></i></span></div><div class="stat-value">{{ $stats['delivered_today'] }}</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Jami yetkazgan</span><span class="stat-icon"><i class="ti ti-trophy"></i></span></div><div class="stat-value">{{ $stats['delivered'] }}</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Aylanma</span><span class="stat-icon"><i class="ti ti-cash"></i></span></div><div class="stat-value">{{ \App\Support\Money::format($stats['turnover'], false) }}</div><div class="stat-hint">so'm, yetkazilganlar</div></div>
    </div>

    @if($p?->last_location_at)
        <div class="alert alert-info"><i class="ti ti-map-pin"></i>
            <div>Oxirgi joylashuv: {{ $p->last_location_at->diffForHumans() }} ·
                <a class="link" target="_blank" rel="noopener" href="https://yandex.uz/maps/?pt={{ $p->last_lng }},{{ $p->last_lat }}&z=16&l=map">xaritada ko'rish</a></div>
        </div>
    @endif

    <section class="card card-flush">
        <div class="card-header"><h2 class="card-title"><i class="ti ti-receipt"></i> Buyurtmalari</h2></div>
        @include('admin.partials.orders-table', ['orders' => $orders, 'showCourier' => false])
    </section>
    {{ $orders->links() }}
@endsection

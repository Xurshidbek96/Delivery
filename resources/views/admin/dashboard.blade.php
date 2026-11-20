@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Bosh sahifa</h1>
            <p class="page-subtitle">{{ \App\Support\UzDate::long(now()) }} · bugungi holat</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="btn btn-primary"><i class="ti ti-clock"></i> Yangi buyurtmalar</a>
        </div>
    </div>

    <div class="stats">
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="stat tone-warning">
            <div class="stat-top"><span class="stat-label">Tasdiq kutmoqda</span><span class="stat-icon"><i class="ti ti-clock"></i></span></div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-hint">Moderator ko'rib chiqishi kerak</div>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="stat tone-info">
            <div class="stat-top"><span class="stat-label">Jarayonda</span><span class="stat-icon"><i class="ti ti-progress"></i></span></div>
            <div class="stat-value">{{ $stats['in_progress'] }}</div>
            <div class="stat-hint">Tasdiqlangan va yo'ldagi</div>
        </a>
        <div class="stat tone-success">
            <div class="stat-top"><span class="stat-label">Bugun yetkazildi</span><span class="stat-icon"><i class="ti ti-package"></i></span></div>
            <div class="stat-value">{{ $stats['delivered_today'] }}</div>
            <div class="stat-hint">Bugun jami {{ $stats['today'] }} ta buyurtma</div>
        </div>
        <div class="stat">
            <div class="stat-top"><span class="stat-label">Bugungi daromad</span><span class="stat-icon"><i class="ti ti-coin"></i></span></div>
            <div class="stat-value">{{ \App\Support\Money::format($stats['income_today'], false) }}</div>
            <div class="stat-hint">Aylanma: {{ \App\Support\Money::format($stats['turnover_today']) }}</div>
        </div>
        <a href="{{ route('admin.couriers.index', ['state' => 'online']) }}" class="stat">
            <div class="stat-top"><span class="stat-label">Kuryerlar onlayn</span><span class="stat-icon"><i class="ti ti-motorbike"></i></span></div>
            <div class="stat-value">{{ $stats['couriers_online'] }} <span class="subtle" style="font-size:1rem">/ {{ $stats['couriers_total'] }}</span></div>
            <div class="stat-hint">Faol kuryerlardan</div>
        </a>
    </div>

    <div class="grid-dashboard">
        <section class="card card-flush">
            <div class="card-header">
                <h2 class="card-title"><i class="ti ti-list-check"></i> Ochiq buyurtmalar</h2>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-ghost">Barchasi <i class="ti ti-arrow-right"></i></a>
            </div>
            @include('admin.partials.orders-table', ['orders' => $openOrders, 'showCourier' => true])
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title"><i class="ti ti-chart-bar"></i> So'nggi 7 kun</h2></div>
            <div class="card-body">
                @php($max = max(1, max(array_column($week, 'count'))))
                <div class="bars" role="img" aria-label="So'nggi 7 kundagi buyurtmalar soni">
                    @foreach($week as $day)
                        <div class="bar">
                            <span class="bar-value">{{ $day['count'] }}</span>
                            <span class="bar-fill" style="height: {{ max(3, round($day['count'] / $max * 100)) }}%"></span>
                            <span class="bar-label">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="subtle" style="margin-top:1rem">Jami: <b>{{ array_sum(array_column($week, 'count')) }}</b> ta buyurtma</p>
            </div>
        </section>
    </div>
@endsection

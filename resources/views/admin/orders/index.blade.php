@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Buyurtmalar</h1>
            <p class="page-subtitle">Yangi buyurtmani tasdiqlang va kuryerni tanlang</p>
        </div>
    </div>

    <nav class="tabs" aria-label="Holat bo'yicha">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.orders.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => request('q')])) }}"
               @class(['tab', 'active' => $tab === $key])>
                {{ $label }} <span class="tab-count">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="card">
        <div class="card-body toolbar">
            @if($tab !== 'all')<input type="hidden" name="status" value="{{ $tab }}">@endif
            <div class="field grow">
                <label class="label" for="q">Qidiruv</label>
                <input class="input" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="D000123, telefon, manzil yoki ism">
            </div>
            <div class="field">
                <label class="label" for="courier_id">Kuryer</label>
                <select class="select" id="courier_id" name="courier_id">
                    <option value="">Barchasi</option>
                    @foreach($couriers as $c)
                        <option value="{{ $c->id }}" @selected((string) request('courier_id') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="from">Sanadan</label>
                <input class="input" id="from" type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="field">
                <label class="label" for="to">Sanagacha</label>
                <input class="input" id="to" type="date" name="to" value="{{ request('to') }}">
            </div>
            <div class="row">
                <button class="btn btn-primary" type="submit"><i class="ti ti-filter"></i> Saralash</button>
                @if(request()->hasAny(['q', 'courier_id', 'from', 'to']))
                    <a href="{{ route('admin.orders.index', array_filter(['status' => $tab === 'all' ? null : $tab])) }}" class="btn btn-ghost">Tozalash</a>
                @endif
            </div>
        </div>
    </form>

    <section class="card card-flush">
        @include('admin.partials.orders-table', ['orders' => $orders])
    </section>

    {{ $orders->links() }}
@endsection

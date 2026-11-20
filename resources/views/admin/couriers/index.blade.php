@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Kuryerlar</h1>
            <p class="page-subtitle">Kuryer ilovasiga login: telefon raqam + parol</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.couriers.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i> Kuryer qo'shish</a>
        </div>
    </div>

    <form method="GET" class="card">
        <div class="card-body toolbar">
            <div class="field grow">
                <label class="label" for="q">Qidiruv</label>
                <input class="input" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Ism yoki telefon">
            </div>
            <div class="field">
                <label class="label" for="state">Holat</label>
                <select class="select" id="state" name="state" onchange="this.form.submit()">
                    <option value="">Barchasi</option>
                    <option value="online" @selected(request('state') === 'online')>Onlayn</option>
                    <option value="blocked" @selected(request('state') === 'blocked')>Bloklangan</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i> Qidirish</button>
        </div>
    </form>

    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kuryer</th><th>Transport</th><th>Holat</th><th class="num">Faol</th><th class="num">Yetkazgan</th><th class="shrink"></th></tr></thead>
                <tbody>
                @forelse($couriers as $c)
                    <tr class="row-link" data-href="{{ route('admin.couriers.show', $c) }}">
                        <td>
                            <div class="cell-user">
                                <span class="avatar">{{ $c->initials() }}</span>
                                <div><div class="cell-main">{{ $c->name }}</div><div class="cell-sub">{{ \App\Support\Phone::format($c->phone) }}</div></div>
                            </div>
                        </td>
                        <td>{{ $c->courierProfile?->vehicle->label() }}@if($c->courierProfile?->vehicle_number)<div class="cell-sub mono">{{ $c->courierProfile->vehicle_number }}</div>@endif</td>
                        <td>
                            @if(! $c->is_active)
                                <span class="badge badge-danger"><i class="ti ti-lock"></i>Bloklangan</span>
                            @elseif($c->courierProfile?->is_online)
                                <span class="badge badge-success"><span class="dot-status on"></span>Onlayn</span>
                            @else
                                <span class="badge badge-secondary"><span class="dot-status"></span>Oflayn</span>
                            @endif
                        </td>
                        <td class="num">{{ $c->active_orders_count }}</td>
                        <td class="num">{{ $c->delivered_count }}</td>
                        <td class="shrink"><a href="{{ route('admin.couriers.edit', $c) }}" class="btn btn-sm btn-ghost" aria-label="Tahrirlash"><i class="ti ti-pencil"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="ti-motorbike" title="Kuryerlar yo'q"><a class="link" href="{{ route('admin.couriers.create') }}">Birinchi kuryerni qo'shing</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $couriers->links() }}
@endsection

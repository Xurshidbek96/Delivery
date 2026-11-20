@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Mijozlar</h1>
            <p class="page-subtitle">Mijoz ilovasi orqali SMS kod bilan ro'yxatdan o'tganlar</p>
        </div>
    </div>

    <form method="GET" class="card">
        <div class="card-body toolbar">
            <div class="field grow">
                <label class="label" for="q">Qidiruv</label>
                <input class="input" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Ism yoki telefon">
            </div>
            <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i> Qidirish</button>
        </div>
    </form>

    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Mijoz</th><th>Ro'yxatdan o'tgan</th><th>Oxirgi buyurtma</th><th class="num">Buyurtmalar</th><th class="num">Xarid summasi</th><th>Holat</th></tr></thead>
                <tbody>
                @forelse($clients as $c)
                    <tr class="row-link" data-href="{{ route('admin.clients.show', $c) }}">
                        <td>
                            <div class="cell-user">
                                <span class="avatar">{{ $c->initials() }}</span>
                                <div><div class="cell-main">{{ $c->name }}</div><div class="cell-sub">{{ \App\Support\Phone::format($c->phone) }}</div></div>
                            </div>
                        </td>
                        <td>{{ $c->created_at->format('d.m.Y') }}</td>
                        <td>{{ $c->last_order_at ? \Illuminate\Support\Carbon::parse($c->last_order_at)->format('d.m.Y H:i') : '—' }}</td>
                        <td class="num">{{ $c->client_orders_count }}</td>
                        <td class="num">{{ \App\Support\Money::format((int) $c->spent) }}</td>
                        <td>@if($c->is_active)<span class="badge badge-success">Faol</span>@else<span class="badge badge-danger">Bloklangan</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="ti-users" title="Mijozlar hali yo'q" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $clients->links() }}
@endsection

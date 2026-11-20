@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Do'konlar</h1>
            <p class="page-subtitle">Faol do'konlar va ularning faol mahsulotlari mijoz ilovasida ko'rinadi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline"><i class="ti ti-package"></i> Barcha mahsulotlar</a>
            <a href="{{ route('admin.shops.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i> Do'kon qo'shish</a>
        </div>
    </div>

    <form method="GET" class="card">
        <div class="card-body toolbar">
            <div class="field grow">
                <label class="label" for="q">Qidiruv</label>
                <input class="input" id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nomi yoki manzili">
            </div>
            <div class="field">
                <label class="label" for="state">Holat</label>
                <select class="select" id="state" name="state" onchange="this.form.submit()">
                    <option value="">Barchasi</option>
                    <option value="active" @selected(request('state') === 'active')>Faol</option>
                    <option value="inactive" @selected(request('state') === 'inactive')>Nofaol</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i> Qidirish</button>
        </div>
    </form>

    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Do'kon</th><th>Manzil</th><th>Holat</th><th class="num">Mahsulotlar</th><th class="shrink"></th></tr></thead>
                <tbody>
                @forelse($shops as $s)
                    <tr class="row-link" data-href="{{ route('admin.shops.show', $s) }}">
                        <td>
                            <div class="cell-user">
                                @include('admin.partials.thumb', ['model' => $s, 'icon' => 'ti-building-store'])
                                <div><div class="cell-main">{{ $s->name }}</div>@if($s->phone)<div class="cell-sub">{{ \App\Support\Phone::format($s->phone) }}</div>@endif</div>
                            </div>
                        </td>
                        <td>{{ $s->address ?? '—' }}</td>
                        <td>
                            @if($s->is_active)
                                <span class="badge badge-success"><i class="ti ti-eye"></i>Faol</span>
                            @else
                                <span class="badge badge-secondary"><i class="ti ti-eye-off"></i>Nofaol</span>
                            @endif
                        </td>
                        <td class="num">{{ $s->active_products_count }} / {{ $s->products_count }}</td>
                        <td class="shrink"><a href="{{ route('admin.shops.edit', $s) }}" class="btn btn-sm btn-ghost" aria-label="Tahrirlash"><i class="ti ti-pencil"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="ti-building-store" title="Do'konlar yo'q"><a class="link" href="{{ route('admin.shops.create') }}">Birinchi do'konni qo'shing</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $shops->links() }}
@endsection

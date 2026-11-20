{{-- Mahsulotlar jadvali. $showShop — do'kon ustuni (do'kon sahifasida kerak emas) --}}
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Mahsulot</th>@if($showShop)<th>Do'kon</th>@endif<th class="num">Taxminiy narx</th><th>Holat</th><th class="shrink"></th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr class="row-link" data-href="{{ route('admin.products.edit', $p) }}">
                <td>
                    <div class="cell-user">
                        @include('admin.partials.thumb', ['model' => $p, 'icon' => 'ti-package'])
                        <div><div class="cell-main">{{ $p->name }}</div><div class="cell-sub">{{ $p->unit->label() }}@if($p->description) · {{ \Illuminate\Support\Str::limit($p->description, 60) }}@endif</div></div>
                    </div>
                </td>
                @if($showShop)<td><a class="link" href="{{ route('admin.shops.show', $p->shop) }}">{{ $p->shop->name }}</a></td>@endif
                <td class="num">{{ $p->price !== null ? \App\Support\Money::format($p->price).' / '.$p->unit->label() : '—' }}</td>
                <td>
                    @if(! $p->is_active)
                        <span class="badge badge-secondary"><i class="ti ti-eye-off"></i>Nofaol</span>
                    @elseif(! $p->shop->is_active)
                        <span class="badge badge-warning"><i class="ti ti-eye-off"></i>Do'kon nofaol</span>
                    @else
                        <span class="badge badge-success"><i class="ti ti-eye"></i>Ko'rinadi</span>
                    @endif
                </td>
                <td class="shrink">
                    <form method="POST" action="{{ route('admin.products.toggle', $p) }}">
                        @csrf
                        <button class="btn btn-sm btn-ghost" type="submit" aria-label="{{ $p->is_active ? 'Nofaol qilish' : 'Faollashtirish' }}" title="{{ $p->is_active ? 'Nofaol qilish' : 'Faollashtirish' }}"><i class="ti {{ $p->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $showShop ? 5 : 4 }}"><x-empty icon="ti-package" title="Mahsulotlar yo'q"></x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>

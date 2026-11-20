{{-- Buyurtmalar jadvali: $orders (client, courier, items_count yuklangan) --}}
@php($showClient = $showClient ?? true)
@php($showCourier = $showCourier ?? true)
<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th>Buyurtma</th>
            @if($showClient)<th>Mijoz</th>@endif
            <th>Manzil</th>
            <th>Holat</th>
            @if($showCourier)<th>Kuryer</th>@endif
            <th class="num">Summa</th>
        </tr>
        </thead>
        <tbody>
        @forelse($orders as $order)
            <tr class="row-link" data-href="{{ route('admin.orders.show', $order) }}">
                <td>
                    <a href="{{ route('admin.orders.show', $order) }}" class="cell-main">{{ $order->number }}</a>
                    <div class="cell-sub">{{ $order->created_at->format('d.m H:i') }} · {{ $order->items_count }} ta mahsulot</div>
                </td>
                @if($showClient)
                    <td>
                        <div class="cell-main">{{ $order->contact_name ?: $order->client->name }}</div>
                        <div class="cell-sub nowrap">{{ \App\Support\Phone::format($order->contact_phone) }}</div>
                    </td>
                @endif
                <td style="max-width:280px">
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $order->address }}</div>
                    @if($order->landmark)<div class="cell-sub" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $order->landmark }}</div>@endif
                </td>
                <td><x-status-badge :status="$order->status" /></td>
                @if($showCourier)
                    <td>
                        @if($order->courier)
                            <div class="cell-user"><span class="avatar" style="width:28px;height:28px;font-size:.75rem">{{ $order->courier->initials() }}</span>{{ $order->courier->name }}</div>
                        @else
                            <span class="subtle">—</span>
                        @endif
                    </td>
                @endif
                <td class="num">
                    <div class="cell-main">{{ \App\Support\Money::format($order->total) }}</div>
                    @if($order->items_total === 0 && ! $order->status->isFinal())<div class="cell-sub">+ xarid summasi</div>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty icon="ti-receipt-off" title="Buyurtmalar yo'q" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>

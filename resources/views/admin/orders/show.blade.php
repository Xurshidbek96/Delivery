@extends('layouts.admin')

@php
    use App\Enums\OrderItemStatus;
    use App\Enums\OrderStatus;
    use App\Support\Money;
    use App\Support\Phone;

    $flow = [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Assigned, OrderStatus::Shopping, OrderStatus::Delivering, OrderStatus::Delivered];
    $cancelled = $order->status === OrderStatus::Cancelled;
    $reached = $cancelled
        ? array_search($order->histories->where('to_status', '!=', OrderStatus::Cancelled)->last()?->to_status ?? OrderStatus::Pending, $flow, true)
        : array_search($order->status, $flow, true);
@endphp

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.orders.index') }}">Buyurtmalar</a><i class="ti ti-chevron-right"></i>{{ $order->number }}</div>
            <div class="row">
                <h1 class="page-title">{{ $order->number }}</h1>
                <x-status-badge :status="$order->status" />
            </div>
            <p class="page-subtitle">{{ $order->created_at->format('d.m.Y H:i') }} da yaratilgan · {{ $order->items->count() }} ta mahsulot</p>
        </div>
        <div class="page-actions">
            <a href="tel:{{ $order->contact_phone }}" class="btn btn-outline"><i class="ti ti-phone"></i> Mijozga qo'ng'iroq</a>
            @unless($order->status->isFinal())
                <button type="button" class="btn btn-danger" data-modal-open="cancel-modal"><i class="ti ti-x"></i> Bekor qilish</button>
            @endunless
        </div>
    </div>

    <div @class(['steps', 'cancelled' => $cancelled]) aria-hidden="true">
        @foreach($flow as $i => $step)<span @class(['done' => $i <= $reached])></span>@endforeach
    </div>

    <div class="grid-main">
        {{-- ======== Chap ustun ======== --}}
        <div class="stack">
            {{-- Amal kartasi --}}
            @if($order->status === OrderStatus::Pending)
                <section class="card" style="border-color: var(--warning)">
                    <div class="card-header"><h2 class="card-title"><i class="ti ti-clipboard-check"></i> Buyurtmani tasdiqlash</h2></div>
                    <form method="POST" action="{{ route('admin.orders.confirm', $order) }}" class="card-body form-grid">
                        @csrf
                        <p class="muted">Ro'yxat va manzilni tekshiring. Kerak bo'lsa mijozga qo'ng'iroq qilib aniqlashtiring.</p>
                        <div class="form-grid cols-2">
                            <x-field name="delivery_fee" label="Yetkazish narxi" hint="Standart narx sozlamalardan olingan">
                                <div class="input-group">
                                    <input class="input" id="delivery_fee" type="number" name="delivery_fee" min="0" step="500" value="{{ old('delivery_fee', $order->delivery_fee) }}">
                                    <span class="suffix">so'm</span>
                                </div>
                            </x-field>
                            <x-field name="staff_note" label="Ichki izoh" hint="Faqat xodimlar ko'radi">
                                <input class="input" id="staff_note" name="staff_note" value="{{ old('staff_note', $order->staff_note) }}" placeholder="Masalan: mijoz 18:00 dan keyin uyda">
                            </x-field>
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-success btn-lg" type="submit"><i class="ti ti-check"></i> Tasdiqlash</button>
                        </div>
                    </form>
                </section>
            @endif

            @if($canAssign)
                <section class="card" style="border-color: var(--brand)">
                    <div class="card-header">
                        <h2 class="card-title"><i class="ti ti-motorbike"></i> {{ $order->courier ? 'Kuryerni almashtirish' : 'Kuryerni tanlang' }}</h2>
                        <span class="subtle">Onlayn va bo'shlar tepada</span>
                    </div>
                    <form method="POST" action="{{ route('admin.orders.assign', $order) }}" class="card-body stack">
                        @csrf
                        @if($candidates->isEmpty())
                            <x-empty icon="ti-user-off" title="Faol kuryer yo'q"><a class="link" href="{{ route('admin.couriers.create') }}">Kuryer qo'shish</a></x-empty>
                        @else
                            <div class="courier-list" role="radiogroup">
                                @foreach($candidates as $c)
                                    <label @class(['courier-option', 'busy' => ! $c->has_capacity])>
                                        <input type="radio" name="courier_id" value="{{ $c->id }}" @checked(old('courier_id', $order->courier_id) == $c->id) @disabled(! $c->has_capacity && $order->courier_id !== $c->id)>
                                        <span class="avatar">{{ $c->initials() }}</span>
                                        <span style="min-width:0">
                                            <span class="row" style="gap:.4rem"><span class="dot-status {{ $c->courierProfile->is_online ? 'on' : '' }}"></span><b>{{ $c->name }}</b></span>
                                            <span class="meta">{{ $c->courierProfile->vehicle->label() }} · {{ Phone::format($c->phone) }}</span>
                                        </span>
                                        <span class="side">
                                            {{ $c->active_orders_count }} ta faol
                                            @if($c->distance_km !== null)<br><span class="subtle">{{ $c->distance_km }} km</span>@endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-actions">
                                <button class="btn btn-primary btn-lg" type="submit"><i class="ti ti-send"></i> Kuryerga yuborish</button>
                            </div>
                        @endif
                    </form>
                </section>
            @endif

            @if($cancelled)
                <div class="alert alert-danger"><i class="ti ti-circle-x"></i>
                    <div><b>Bekor qilindi</b> ({{ $order->cancelled_by_role?->label() }}, {{ $order->cancelled_at?->format('d.m H:i') }}): {{ $order->cancel_reason }}</div>
                </div>
            @endif

            {{-- Mahsulotlar --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ti ti-basket"></i> Bozorlik ro'yxati</h2>
                    @php($done = $order->items->where('status', '!=', OrderItemStatus::Pending)->count())
                    <span class="subtle">{{ $done }} / {{ $order->items->count() }} belgilangan</span>
                </div>
                <ul class="items">
                    @foreach($order->items as $item)
                        <li @class(['item', 'unavailable' => $item->status === OrderItemStatus::Unavailable])>
                            <span class="item-index">{{ $loop->iteration }}</span>
                            <div class="item-body">
                                <div class="item-name">{{ $item->name }}</div>
                                <div class="item-meta">{{ $item->quantityLabel() }}@if($item->shop_name) · <i class="ti ti-building-store"></i> {{ $item->shop_name }}@endif @if($item->note) · {{ $item->note }}@endif</div>
                                @if($item->courier_note)<div class="item-meta"><i class="ti ti-message-circle"></i> Kuryer: {{ $item->courier_note }}</div>@endif
                            </div>
                            <div class="item-side">
                                @if($item->price)<b>{{ Money::format($item->price) }}</b>@endif
                                @if($item->status !== OrderItemStatus::Pending)<span class="badge badge-{{ $item->status->tone() }}">{{ $item->status->label() }}</span>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="card-footer">
                    <dl class="dl dl-inline">
                        <div><dt>Mahsulotlar (xarid)</dt><dd>{{ $order->items_total ? Money::format($order->items_total) : 'Kuryer xariddan keyin' }}</dd></div>
                        <div><dt>Yetkazish</dt><dd>{{ Money::format($order->delivery_fee) }}</dd></div>
                        @if($order->service_fee)<div><dt>Xizmat haqi</dt><dd>{{ Money::format($order->service_fee) }}</dd></div>@endif
                        <div class="total"><dt>Jami</dt><dd>{{ Money::format($order->total) }}</dd></div>
                        <div><dt>To'lov</dt><dd>{{ $order->payment_method->label() }} · <span @class(['text-success' => $order->payment_status->value === 'paid'])>{{ $order->payment_status->label() }}</span></dd></div>
                    </dl>
                </div>
            </section>

            @if($order->note)
                <section class="card">
                    <div class="card-header"><h2 class="card-title"><i class="ti ti-message"></i> Mijoz izohi</h2></div>
                    <div class="card-body">{{ $order->note }}</div>
                </section>
            @endif

            @if($order->receiptUrl())
                <section class="card">
                    <div class="card-header"><h2 class="card-title"><i class="ti ti-file-invoice"></i> Chek</h2></div>
                    <div class="card-body"><a href="{{ $order->receiptUrl() }}" target="_blank" rel="noopener"><img src="{{ $order->receiptUrl() }}" alt="Chek rasmi" style="max-width:100%;max-height:420px;border-radius:12px"></a></div>
                </section>
            @endif
        </div>

        {{-- ======== O'ng ustun ======== --}}
        <div class="stack">
            <section class="card">
                <div class="card-header"><h2 class="card-title"><i class="ti ti-user"></i> Mijoz</h2></div>
                <div class="card-body stack">
                    <div class="cell-user">
                        <span class="avatar">{{ $order->client->initials() }}</span>
                        <div>
                            <a class="cell-main link" href="{{ route('admin.clients.show', $order->client) }}">{{ $order->client->name }}</a>
                            <div class="cell-sub">{{ $clientOrdersCount }} ta buyurtma</div>
                        </div>
                    </div>
                    <dl class="dl">
                        <div><dt>Qabul qiluvchi</dt><dd>{{ $order->contact_name }}</dd></div>
                        <div><dt>Telefon</dt><dd><a class="link" href="tel:{{ $order->contact_phone }}">{{ Phone::format($order->contact_phone) }}</a></dd></div>
                    </dl>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="ti ti-map-pin"></i> Manzil</h2>
                    @if($order->mapUrl())<a href="{{ $order->mapUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost"><i class="ti ti-map-2"></i> Xaritada</a>@endif
                </div>
                <div class="card-body">
                    <dl class="dl">
                        <div><dt>Manzil</dt><dd>{{ $order->fullAddress() }}</dd></div>
                        @if($order->landmark)<div><dt>Mo'ljal</dt><dd>{{ $order->landmark }}</dd></div>@endif
                    </dl>
                </div>
            </section>

            @if($order->courier)
                <section class="card">
                    <div class="card-header"><h2 class="card-title"><i class="ti ti-motorbike"></i> Kuryer</h2></div>
                    <div class="card-body">
                        <div class="cell-user">
                            <span class="avatar">{{ $order->courier->initials() }}</span>
                            <div>
                                <a class="cell-main link" href="{{ route('admin.couriers.show', $order->courier) }}">{{ $order->courier->name }}</a>
                                <div class="cell-sub"><a href="tel:{{ $order->courier->phone }}">{{ Phone::format($order->courier->phone) }}</a> · {{ $order->courier->courierProfile?->vehicle->label() }}</div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-header"><h2 class="card-title"><i class="ti ti-notes"></i> Ichki izoh</h2></div>
                <form method="POST" action="{{ route('admin.orders.note', $order) }}" class="card-body stack-sm">
                    @csrf @method('PATCH')
                    <textarea class="textarea" name="staff_note" rows="3" placeholder="Faqat xodimlar ko'radi">{{ old('staff_note', $order->staff_note) }}</textarea>
                    <div><button class="btn btn-sm btn-outline" type="submit">Saqlash</button></div>
                </form>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title"><i class="ti ti-history"></i> Tarix</h2></div>
                <div class="card-body">
                    <ol class="timeline">
                        @foreach($order->histories->reverse() as $h)
                            <li>
                                <span class="t-icon"><i class="ti {{ $h->to_status->icon() }}"></i></span>
                                <div>
                                    <div class="t-title">{{ $h->to_status->label() }}</div>
                                    <div class="t-meta">{{ $h->created_at->format('d.m H:i') }} · {{ $h->actor?->name ?? 'Tizim' }}@if($h->actor_role) ({{ $h->actor_role->label() }})@endif</div>
                                    @if($h->comment)<div class="t-comment">{{ $h->comment }}</div>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        </div>
    </div>

    @unless($order->status->isFinal())
        <dialog class="modal" id="cancel-modal" @if($errors->has('reason')) data-open-on-load @endif>
            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title">{{ $order->number }} ni bekor qilish</h2>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Yopish"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">
                    <x-field name="reason" label="Sabab" hint="Mijozga bildirishnoma sifatida yuboriladi">
                        <textarea class="textarea" id="reason" name="reason" required minlength="3" maxlength="255" placeholder="Masalan: manzil xizmat hududidan tashqarida">{{ old('reason') }}</textarea>
                    </x-field>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-modal-close>Orqaga</button>
                    <button type="submit" class="btn btn-danger" style="background:var(--danger);color:#fff"><i class="ti ti-x"></i> Bekor qilish</button>
                </div>
            </form>
        </dialog>
    @endunless
@endsection

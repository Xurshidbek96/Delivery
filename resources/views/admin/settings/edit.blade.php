@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Sozlamalar</h1>
            <p class="page-subtitle">O'zgarishlar darhol ilovalarga ta'sir qiladi</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="stack" style="max-width:820px">
        @csrf @method('PUT')

        <section class="card">
            <div class="card-header"><h2 class="card-title"><i class="ti ti-power"></i> Buyurtma qabul qilish</h2></div>
            <div class="card-body">
                <label class="switch">
                    <input type="checkbox" name="accepting_orders" value="1" @checked(old('accepting_orders', $settings['accepting_orders']))>
                    <span class="track"></span> Yangi buyurtmalar qabul qilinsin
                </label>
                <p class="hint" style="margin-top:.5rem">O'chirilsa, mijoz ilovasi buyurtma bera olmaydi (masalan, ish vaqtidan tashqari).</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title"><i class="ti ti-coin"></i> Narxlar</h2></div>
            <div class="card-body form-grid cols-2">
                <x-field name="delivery_fee" label="Yetkazish narxi" hint="Har bir buyurtma uchun; moderator tasdiqlashda o'zgartira oladi">
                    <div class="input-group"><input class="input" id="delivery_fee" type="number" name="delivery_fee" min="0" step="500" value="{{ old('delivery_fee', $settings['delivery_fee']) }}" required><span class="suffix">so'm</span></div>
                </x-field>
                <x-field name="service_fee" label="Xizmat haqi" hint="0 — olinmaydi">
                    <div class="input-group"><input class="input" id="service_fee" type="number" name="service_fee" min="0" step="500" value="{{ old('service_fee', $settings['service_fee']) }}" required><span class="suffix">so'm</span></div>
                </x-field>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title"><i class="ti ti-adjustments"></i> Cheklovlar</h2></div>
            <div class="card-body form-grid cols-2">
                <x-field name="courier_max_active_orders" label="Kuryerda bir vaqtda buyurtma" hint="Shundan ko'p bo'lsa kuryerga berib bo'lmaydi">
                    <input class="input" id="courier_max_active_orders" type="number" name="courier_max_active_orders" min="1" max="20" value="{{ old('courier_max_active_orders', $settings['courier_max_active_orders']) }}" required>
                </x-field>
                <x-field name="max_items_per_order" label="Buyurtmada mahsulotlar soni (max)">
                    <input class="input" id="max_items_per_order" type="number" name="max_items_per_order" min="1" max="200" value="{{ old('max_items_per_order', $settings['max_items_per_order']) }}" required>
                </x-field>
                <x-field name="support_phone" label="Qo'llab-quvvatlash telefoni" hint="Ilovalarda ko'rsatiladi">
                    <input class="input" id="support_phone" name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" required>
                </x-field>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title"><i class="ti ti-credit-card"></i> To'lov usullari</h2></div>
            <div class="card-body stack-sm">
                @foreach(\App\Enums\PaymentMethod::cases() as $m)
                    <div class="row between">
                        <span class="strong">{{ $m->label() }}</span>
                        @if($m->isEnabled())<span class="badge badge-success">Ishlayapti</span>@else<span class="badge badge-secondary">Tez kunda</span>@endif
                    </div>
                @endforeach
            </div>
        </section>

        <div class="form-actions">
            <button class="btn btn-primary btn-lg" type="submit"><i class="ti ti-device-floppy"></i> Saqlash</button>
        </div>
    </form>
@endsection

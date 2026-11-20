@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.couriers.index') }}">Kuryerlar</a><i class="ti ti-chevron-right"></i>{{ $courier ? $courier->name : 'Yangi' }}</div>
            <h1 class="page-title">{{ $title }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $courier ? route('admin.couriers.update', $courier) : route('admin.couriers.store') }}" class="card" style="max-width:820px">
        @csrf
        @if($courier) @method('PUT') @endif
        <div class="card-body form-grid">
            <div class="form-grid cols-2">
                <x-field name="name" label="F.I.Sh.">
                    <input class="input" id="name" name="name" value="{{ old('name', $courier?->name) }}" required>
                </x-field>
                <x-field name="phone" label="Telefon (login)">
                    <input class="input" id="phone" name="phone" type="tel" value="{{ old('phone', $courier?->phone ?? '+998') }}" required>
                </x-field>
                <x-field name="password" label="{{ $courier ? 'Yangi parol' : 'Parol' }}" :hint="$courier ? 'Bo\'sh qoldirsangiz o\'zgarmaydi. O\'zgarsa — ilovadan chiqarib yuboriladi.' : 'Kamida 6 belgi. Kuryerga yetkazing.'">
                    <input class="input" id="password" name="password" type="text" autocomplete="new-password" @required(! $courier)>
                </x-field>
                <x-field name="vehicle" label="Transport">
                    <select class="select" id="vehicle" name="vehicle">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->value }}" @selected(old('vehicle', $courier?->courierProfile?->vehicle->value) === $v->value)>{{ $v->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="vehicle_number" label="Davlat raqami" hint="Ixtiyoriy">
                    <input class="input" id="vehicle_number" name="vehicle_number" value="{{ old('vehicle_number', $courier?->courierProfile?->vehicle_number) }}" placeholder="01 A 123 BC">
                </x-field>
            </div>
            <x-field name="note" label="Izoh" hint="Faqat xodimlar ko'radi">
                <textarea class="textarea" id="note" name="note" rows="2">{{ old('note', $courier?->courierProfile?->note) }}</textarea>
            </x-field>
            <label class="switch">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $courier?->is_active ?? true))>
                <span class="track"></span> Faol (o'chirilsa — ilovaga kira olmaydi)
            </label>
        </div>
        <div class="card-footer form-actions">
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> Saqlash</button>
            <a href="{{ $courier ? route('admin.couriers.show', $courier) : route('admin.couriers.index') }}" class="btn btn-ghost">Bekor qilish</a>
        </div>
    </form>
@endsection

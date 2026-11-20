@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="{{ route('admin.staff.index') }}">Xodimlar</a><i class="ti ti-chevron-right"></i>{{ $staff ? $staff->name : 'Yangi' }}</div>
            <h1 class="page-title">{{ $title }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $staff ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" class="card" style="max-width:820px">
        @csrf
        @if($staff) @method('PUT') @endif
        <div class="card-body form-grid">
            <div class="form-grid cols-2">
                <x-field name="name" label="F.I.Sh.">
                    <input class="input" id="name" name="name" value="{{ old('name', $staff?->name) }}" required>
                </x-field>
                <x-field name="phone" label="Telefon (login)">
                    <input class="input" id="phone" name="phone" type="tel" value="{{ old('phone', $staff?->phone ?? '+998') }}" required>
                </x-field>
                <x-field name="email" label="Email" hint="Ixtiyoriy">
                    <input class="input" id="email" name="email" type="email" value="{{ old('email', $staff?->email) }}">
                </x-field>
                <x-field name="role" label="Rol">
                    <select class="select" id="role" name="role">
                        @foreach($roles as $r)
                            <option value="{{ $r->value }}" @selected(old('role', $staff?->role->value ?? 'moderator') === $r->value)>{{ $r->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="password" label="{{ $staff ? 'Yangi parol' : 'Parol' }}" :hint="$staff ? 'Bo\'sh qoldirsangiz o\'zgarmaydi' : 'Kamida 8 belgi'">
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" @required(! $staff)>
                </x-field>
            </div>
            <label class="switch">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $staff?->is_active ?? true))>
                <span class="track"></span> Faol
            </label>
        </div>
        <div class="card-footer form-actions">
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> Saqlash</button>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-ghost">Bekor qilish</a>
        </div>
    </form>
@endsection

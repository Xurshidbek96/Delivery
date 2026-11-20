@extends('layouts.admin')

@section('content')
    @php($me = auth()->user())
    <div class="page-header">
        <div class="row">
            <span class="avatar avatar-lg">{{ $me->initials() }}</span>
            <div>
                <h1 class="page-title">{{ $me->name }}</h1>
                <p class="page-subtitle">{{ $me->role->label() }} · {{ \App\Support\Phone::format($me->phone) }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.profile.password') }}" class="card" style="max-width:560px">
        @csrf @method('PUT')
        <div class="card-header"><h2 class="card-title"><i class="ti ti-key"></i> Parolni almashtirish</h2></div>
        <div class="card-body form-grid">
            <x-field name="current_password" label="Joriy parol">
                <input class="input" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            </x-field>
            <x-field name="password" label="Yangi parol" hint="Kamida 8 belgi">
                <input class="input" id="password" type="password" name="password" autocomplete="new-password" required>
            </x-field>
            <x-field name="password_confirmation" label="Yangi parolni takrorlang">
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </x-field>
        </div>
        <div class="card-footer form-actions">
            <button class="btn btn-primary" type="submit">Yangilash</button>
        </div>
    </form>
@endsection

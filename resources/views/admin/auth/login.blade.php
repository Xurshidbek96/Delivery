<!DOCTYPE html>
<html lang="uz">
<head>
    @include('admin.partials.head', ['title' => 'Kirish'])
</head>
<body>
<div class="auth">
    <section class="auth-aside" aria-hidden="true">
        <div class="brand" style="color:#fff;margin-bottom:3rem">
            <span class="brand-mark"><i class="ti ti-truck-delivery"></i></span>
            <span>{{ config('app.name') }}</span>
        </div>
        <h2>Bozorlikdan eshikkacha — bitta paneldan.</h2>
        <p>Buyurtmalarni tasdiqlang, kuryerni tanlang va yetkazib berishni real vaqtda kuzating.</p>
        <div class="flow">
            <div><i class="ti ti-receipt"></i> Mijoz ro'yxat yuboradi</div>
            <div><i class="ti ti-circle-check"></i> Moderator tasdiqlaydi</div>
            <div><i class="ti ti-motorbike"></i> Kuryer xarid qilib yetkazadi</div>
        </div>
    </section>

    <main class="auth-main">
        <div class="auth-card">
            <div class="row between">
                <div class="brand" style="margin:0;padding:0">
                    <span class="brand-mark"><i class="ti ti-truck-delivery"></i></span>
                    <span>{{ config('app.name') }}<small>Boshqaruv paneli</small></span>
                </div>
                <button class="icon-btn" type="button" data-theme-toggle aria-label="Tungi rejim"><i class="ti ti-moon"></i></button>
            </div>

            <div>
                <h1 class="page-title">Xush kelibsiz</h1>
                <p class="page-subtitle">Xodim akkauntingiz bilan kiring</p>
            </div>

            <form method="POST" action="{{ route('admin.login.store') }}" class="form-grid">
                @csrf
                <x-field name="phone" label="Telefon raqam">
                    <input class="input @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" inputmode="tel"
                           value="{{ old('phone', '+998') }}" autocomplete="username" required autofocus>
                </x-field>
                <x-field name="password" label="Parol">
                    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
                </x-field>
                <label class="check"><input type="checkbox" name="remember" value="1" checked> Meni eslab qol</label>
                <button class="btn btn-primary btn-lg btn-block" type="submit"><i class="ti ti-login-2"></i> Kirish</button>
            </form>

            <p class="subtle">Parolni unutdingizmi? Administratorga murojaat qiling.</p>
        </div>
    </main>
</div>
<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
</body>
</html>

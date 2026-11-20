<!DOCTYPE html>
<html lang="uz">
<head>
    @include('admin.partials.head', ['title' => $title ?? null])
</head>
<body data-poll-url="{{ route('admin.orders.pending-count') }}" data-pending="{{ $pendingCount }}">
    @php($me = auth()->user())

    <aside class="sidebar" id="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-mark"><i class="ti ti-truck-delivery"></i></span>
            <span>{{ config('app.name') }}<small>Boshqaruv paneli</small></span>
        </a>

        <nav class="menu" aria-label="Asosiy">
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
                <i class="ti ti-layout-dashboard"></i><span>Bosh sahifa</span>
            </a>
            <a href="{{ route('admin.orders.index') }}" @class(['active' => request()->routeIs('admin.orders.*')])>
                <i class="ti ti-receipt"></i><span>Buyurtmalar</span>
                <span class="count" data-pending-count @if(! $pendingCount) hidden @endif>{{ $pendingCount }}</span>
            </a>
        </nav>

        <div class="menu-label">Foydalanuvchilar</div>
        <nav class="menu" aria-label="Foydalanuvchilar">
            <a href="{{ route('admin.couriers.index') }}" @class(['active' => request()->routeIs('admin.couriers.*')])>
                <i class="ti ti-motorbike"></i><span>Kuryerlar</span>
            </a>
            <a href="{{ route('admin.clients.index') }}" @class(['active' => request()->routeIs('admin.clients.*')])>
                <i class="ti ti-users"></i><span>Mijozlar</span>
            </a>
            @if($me->isAdmin())
                <a href="{{ route('admin.staff.index') }}" @class(['active' => request()->routeIs('admin.staff.*')])>
                    <i class="ti ti-shield-check"></i><span>Xodimlar</span>
                </a>
            @endif
        </nav>

        @if($me->isAdmin())
            <div class="menu-label">Tizim</div>
            <nav class="menu" aria-label="Tizim">
                <a href="{{ route('admin.settings.edit') }}" @class(['active' => request()->routeIs('admin.settings.*')])>
                    <i class="ti ti-adjustments-horizontal"></i><span>Sozlamalar</span>
                </a>
            </nav>
        @endif

        <div class="side-footer">
            <div class="side-user">
                <a href="{{ route('admin.profile.edit') }}" class="avatar">{{ $me->initials() }}</a>
                <a href="{{ route('admin.profile.edit') }}" style="min-width:0">
                    <strong>{{ $me->name }}</strong>
                    <span>{{ $me->role->label() }}</span>
                </a>
            </div>
            <nav class="menu">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout"><i class="ti ti-logout"></i><span>Chiqish</span></button>
                </form>
            </nav>
        </div>
    </aside>
    <div class="backdrop" aria-hidden="true"></div>

    <main class="content">
        <header class="topbar">
            <button class="icon-btn menu-toggle" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Menyu">
                <i class="ti ti-menu-2"></i>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="brand">
                <span class="brand-mark"><i class="ti ti-truck-delivery"></i></span>
                <span>{{ config('app.name') }}</span>
            </a>
            <form class="topbar-search" method="GET" action="{{ route('admin.orders.index') }}" role="search">
                <i class="ti ti-search"></i>
                <input class="input" type="search" name="q" value="{{ request()->routeIs('admin.orders.index') ? request('q') : '' }}" placeholder="Buyurtma raqami, telefon yoki manzil">
            </form>
            <div class="topbar-actions">
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="live-pill" data-pending-pill @if(! $pendingCount) hidden @endif>
                    <span class="pulse"></span><span data-pending-count>{{ $pendingCount }}</span><span class="label">ta yangi</span>
                </a>
                <button class="icon-btn" type="button" data-theme-toggle aria-label="Tungi rejim"><i class="ti ti-moon"></i></button>
            </div>
        </header>

        <div class="container">
            @if(session('success'))
                <div class="alert alert-success" role="status"><i class="ti ti-circle-check"></i><div>{{ session('success') }}</div></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" role="alert"><i class="ti ti-alert-circle"></i><div>{{ session('error') }}</div></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="ti ti-alert-circle"></i>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
    @stack('scripts')
</body>
</html>

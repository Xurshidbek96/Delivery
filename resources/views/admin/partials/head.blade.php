{{-- Admin va kirish sahifasi uchun umumiy <head> qismi --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }} Admin</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%235b4ff0'/%3E%3Cpath d='M9 12h11l3 4v5h-2.2a2.5 2.5 0 0 1-4.6 0h-2.4a2.5 2.5 0 0 1-4.6 0H9z' fill='white'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
<script>
    // Tungi rejim chizilishdan oldin qo'llanadi (oq "chaqnash" bo'lmasin)
    (function () {
        try {
            var t = localStorage.getItem('delivery_admin_theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
</script>

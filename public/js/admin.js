/* Delivery Admin — kichik, kutubxonasiz skriptlar. */
(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;
    var THEME_KEY = 'delivery_admin_theme';

    function safeStore(key, value) {
        try { localStorage.setItem(key, value); } catch (e) { /* xususiy rejim */ }
    }

    // ---------- Tungi rejim ----------
    function syncThemeIcons() {
        var dark = root.classList.contains('dark');
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            var icon = btn.querySelector('i');
            if (icon) icon.className = dark ? 'ti ti-sun' : 'ti ti-moon';
            btn.setAttribute('aria-label', dark ? 'Kunduzgi rejim' : 'Tungi rejim');
            btn.setAttribute('title', dark ? 'Kunduzgi rejim' : 'Tungi rejim');
        });
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var dark = root.classList.toggle('dark');
            safeStore(THEME_KEY, dark ? 'dark' : 'light');
            syncThemeIcons();
        });
    });
    syncThemeIcons();

    // ---------- Yon panel (telefon) ----------
    var toggle = document.querySelector('[data-sidebar-toggle]');
    function setSidebar(open) {
        body.classList.toggle('sidebar-open', open);
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (toggle) toggle.addEventListener('click', function () { setSidebar(!body.classList.contains('sidebar-open')); });
    var backdrop = document.querySelector('.backdrop');
    if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setSidebar(false); });

    // ---------- Butun qator havola ----------
    document.addEventListener('click', function (e) {
        var row = e.target.closest('tr[data-href]');
        if (!row || e.target.closest('a, button, input, select, form, label')) return;
        if (e.metaKey || e.ctrlKey) { window.open(row.dataset.href, '_blank'); return; }
        window.location.href = row.dataset.href;
    });

    // ---------- Tasdiqlash ----------
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    // ---------- Modal ----------
    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-modal-open]');
        if (opener) {
            var dlg = document.getElementById(opener.getAttribute('data-modal-open'));
            if (dlg && dlg.showModal) dlg.showModal();
        }
        var closer = e.target.closest('[data-modal-close]');
        if (closer) {
            var parent = closer.closest('dialog');
            if (parent) parent.close();
        }
    });
    document.querySelectorAll('dialog.modal[data-open-on-load]').forEach(function (d) { d.showModal(); });

    // ---------- Toast ----------
    function toast(text) {
        var stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            body.appendChild(stack);
        }
        var el = document.createElement('div');
        el.className = 'toast';
        el.innerHTML = '<i class="ti ti-bell-ringing"></i>';
        el.appendChild(document.createTextNode(text));
        stack.appendChild(el);
        setTimeout(function () { el.remove(); }, 6000);
    }

    function beep() {
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            var ctx = new Ctx();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.5);
            osc.connect(gain).connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* brauzer ruxsat bermadi */ }
    }

    // ---------- Yangi buyurtmalarni kuzatish (polling) ----------
    var pollUrl = body.getAttribute('data-poll-url');
    if (pollUrl) {
        var baseTitle = document.title;
        var last = parseInt(body.getAttribute('data-pending') || '0', 10);

        var render = function (count) {
            document.querySelectorAll('[data-pending-count]').forEach(function (el) {
                el.textContent = count;
                el.hidden = count === 0;
            });
            document.querySelectorAll('[data-pending-pill]').forEach(function (el) { el.hidden = count === 0; });
            document.title = count > 0 ? '(' + count + ') ' + baseTitle : baseTitle;
        };

        var poll = function () {
            fetch(pollUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    if (data.pending > last) {
                        toast('Yangi buyurtma! Tasdiqlash kutilmoqda: ' + data.pending);
                        beep();
                    }
                    last = data.pending;
                    render(data.pending);
                })
                .catch(function () { /* tarmoq xatosi — keyingi urinish */ });
        };

        render(last);
        setInterval(function () { if (!document.hidden) poll(); }, 15000);
    }
})();

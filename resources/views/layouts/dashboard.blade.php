@include('partials.head')

<style>
/* ═════════════════════════════════════════════════════════
   RUNCONNECT DASHBOARD LAYOUT — Dark Athletic Native App
═════════════════════════════════════════════════════════ */

html, body {
    background: var(--rc-bg);
    color: var(--rc-text);
    min-height: 100dvh;
    padding-bottom: 80px; /* space for bottom nav on mobile */
}

/* ── Top Navbar (Desktop) ─────────────────────────────── */
.rc-navbar {
    background: var(--rc-surface);
    border-bottom: 1px solid var(--rc-border);
    padding: 0.75rem 1.5rem;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.rc-navbar-brand {
    font-weight: 900;
    font-size: 1.15rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--rc-text) !important;
    text-decoration: none !important;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rc-navbar-brand .brand-icon {
    width: 34px;
    height: 34px;
    background: linear-gradient(135deg, var(--rc-green), #00A854);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #0D1117;
}

.rc-navbar .rc-username {
    color: var(--rc-text-muted);
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none !important;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s ease;
}

.rc-navbar .rc-username:hover { color: var(--rc-text); }

.rc-navbar .rc-username .avatar {
    width: 32px;
    height: 32px;
    background: var(--rc-surface-2);
    border: 2px solid var(--rc-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    color: var(--rc-green);
    font-weight: 700;
}

.rc-logout-btn {
    background: var(--rc-surface-2) !important;
    border: 1px solid var(--rc-border) !important;
    color: var(--rc-text-muted) !important;
    border-radius: 10px !important;
    padding: 0.45rem 1rem !important;
    font-size: 0.82rem !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

.rc-logout-btn:hover {
    background: rgba(248,81,73,0.12) !important;
    border-color: rgba(248,81,73,0.3) !important;
    color: #f85149 !important;
}

/* ── Mobile Bottom Navigation ─────────────────────────── */
.rc-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1040;
    padding: 0 1rem 0.5rem;
    background: transparent;
    pointer-events: none;
}

.rc-bottom-nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: var(--rc-surface);
    border: 1px solid var(--rc-border);
    border-radius: 20px;
    padding: 0.6rem 0.5rem;
    box-shadow: 0 -4px 24px rgba(0,0,0,0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    pointer-events: all;
}

.rc-nav-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    text-decoration: none !important;
    color: var(--rc-text-dim) !important;
    transition: color 0.2s ease, transform 0.15s ease;
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    position: relative;
    min-width: 60px;
}

.rc-nav-tab:hover { color: var(--rc-text-muted) !important; }
.rc-nav-tab.active { color: var(--rc-green) !important; }

.rc-nav-tab i { font-size: 1.3rem; }

.rc-nav-tab span {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.rc-nav-tab.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 2.5px;
    background: var(--rc-green);
    border-radius: 0 0 4px 4px;
    box-shadow: 0 0 8px var(--rc-green-glow);
}

.rc-nav-logout {
    background: none;
    border: none;
    cursor: pointer;
}

/* ── Main Content ─────────────────────────────────────── */
.rc-main {
    max-width: 100%;
    padding: 1.5rem 1rem;
}

@media (min-width: 768px) {
    .rc-main { padding: 2rem 2rem; }
    html, body { padding-bottom: 0; }
    .rc-bottom-nav { display: none !important; }
}

/* ── Session Toasts ───────────────────────────────────── */
.rc-session-alert {
    border-radius: 14px;
    border: none;
    padding: 0.875rem 1rem;
    font-size: 0.88rem;
    font-weight: 500;
}

.rc-session-alert.success {
    background: rgba(0,210,106,0.1);
    border: 1px solid rgba(0,210,106,0.2) !important;
    color: var(--rc-green);
}

.rc-session-alert.danger {
    background: rgba(248,81,73,0.1);
    border: 1px solid rgba(248,81,73,0.2) !important;
    color: #f85149;
}

.rc-session-alert .btn-close { filter: invert(1) opacity(0.5); }

/* ── Offline Toast ────────────────────────────────────── */
.rc-toast {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translate(-50%, -120px);
    z-index: 99999;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
    opacity: 0;
    pointer-events: none;
}
.rc-toast.show {
    transform: translate(-50%, 0);
    opacity: 1;
}
.rc-toast-inner {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #1a0f0f;
    border: 1px solid rgba(248,81,73,0.35);
    color: #f85149;
    border-radius: 50px;
    padding: 0.7rem 1.4rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
}

/* ── General Card Component ───────────────────────────── */
.rc-card {
    background: var(--rc-surface);
    border: 1px solid var(--rc-border);
    border-radius: 18px;
    overflow: hidden;
}

.rc-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--rc-border);
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 0.3px;
    color: var(--rc-text);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.rc-card-body { padding: 1.25rem 1.5rem; }

/* ── Badge ────────────────────────────────────────────── */
.badge.bg-primary {
    background: var(--rc-green) !important;
    color: #0D1117 !important;
    border-radius: 8px !important;
}

/* ── Pills / Tabs ─────────────────────────────────────── */
.nav-pills .nav-link {
    color: var(--rc-text-muted) !important;
    background: transparent !important;
    border-radius: 10px !important;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.nav-pills .nav-link:hover { color: var(--rc-text) !important; background: var(--rc-surface-2) !important; }

.nav-pills .nav-link.active {
    background: var(--rc-green) !important;
    color: #0D1117 !important;
    box-shadow: 0 4px 12px var(--rc-green-glow) !important;
}

.nav-tabs {
    border-bottom: 1px solid var(--rc-border) !important;
    gap: 0.5rem;
}

.nav-tabs .nav-link {
    color: var(--rc-text-muted) !important;
    background: transparent !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    border-radius: 0 !important;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.6rem 0.8rem;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link:hover { color: var(--rc-text) !important; }

.nav-tabs .nav-link.active {
    color: var(--rc-green) !important;
    background: transparent !important;
    border-bottom-color: var(--rc-green) !important;
}

/* ── Buttons ──────────────────────────────────────────── */
.btn-outline-secondary {
    border-color: var(--rc-border) !important;
    color: var(--rc-text-muted) !important;
    border-radius: 10px !important;
}
.btn-outline-secondary:hover { background: var(--rc-surface-2) !important; color: var(--rc-text) !important; }

.btn-danger {
    background: rgba(248,81,73,0.15) !important;
    border-color: rgba(248,81,73,0.3) !important;
    color: #f85149 !important;
    border-radius: 10px !important;
}
.btn-danger:hover { background: rgba(248,81,73,0.25) !important; }

/* ── Modal ────────────────────────────────────────────── */
.modal-content {
    background: var(--rc-surface) !important;
    border: 1px solid var(--rc-border) !important;
    border-radius: 20px !important;
    color: var(--rc-text) !important;
}

.modal-header {
    border-bottom: 1px solid var(--rc-border) !important;
    padding: 1.25rem 1.5rem;
}

.modal-header .btn-close { filter: invert(1) opacity(0.5); }

.modal-body { padding: 1.5rem !important; }
.modal-footer { border-top: 1px solid var(--rc-border) !important; padding: 1rem 1.5rem !important; }

/* ── Form Controls (dark) ─────────────────────────────── */
.form-control, .form-select {
    background: var(--rc-surface-2) !important;
    border: 1.5px solid var(--rc-border) !important;
    border-radius: 12px !important;
    color: var(--rc-text) !important;
    padding: 0.65rem 0.9rem !important;
    font-size: 0.9rem !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
}

.form-control::placeholder { color: var(--rc-text-dim) !important; }

.form-control:focus, .form-select:focus {
    background: var(--rc-surface-2) !important;
    border-color: var(--rc-green) !important;
    box-shadow: 0 0 0 3px var(--rc-green-glow) !important;
    color: var(--rc-text) !important;
}

.form-label {
    color: var(--rc-text-muted) !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.input-group-text {
    background: var(--rc-surface-2) !important;
    border: 1.5px solid var(--rc-border) !important;
    color: var(--rc-text-muted) !important;
    border-radius: 12px 0 0 12px !important;
}

/* ── Progress Bar ─────────────────────────────────────── */
.progress {
    background: var(--rc-surface-2) !important;
    border-radius: 99px !important;
}

.progress-bar { border-radius: 99px !important; }

/* ── Table ────────────────────────────────────────────── */
.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--rc-text);
    --bs-table-border-color: var(--rc-border);
    --bs-table-hover-bg: var(--rc-surface-2);
    --bs-table-striped-bg: transparent;
}

.table th {
    color: var(--rc-text-muted) !important;
    font-size: 0.72rem !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    border-color: var(--rc-border) !important;
}

.table td { border-color: var(--rc-border) !important; color: var(--rc-text); }

/* ── Dropdown ─────────────────────────────────────────── */
.dropdown-menu {
    background: var(--rc-surface-2) !important;
    border: 1px solid var(--rc-border) !important;
    border-radius: 14px !important;
    box-shadow: 0 12px 40px rgba(0,0,0,0.5) !important;
}

.dropdown-item { color: var(--rc-text-muted) !important; border-radius: 8px !important; }
.dropdown-item:hover { background: var(--rc-surface-3) !important; color: var(--rc-text) !important; }

/* ── Scrollbar ────────────────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--rc-bg); }
::-webkit-scrollbar-thumb { background: var(--rc-surface-3); border-radius: 99px; }
::-webkit-scrollbar-thumb:hover { background: var(--rc-text-dim); }
</style>
</head>

<body>

{{-- ── Offline Toast ── --}}
<div class="rc-toast" id="offlineToast">
    <div class="rc-toast-inner">
        <i class="fa-solid fa-wifi-slash"></i>
        <span>No Internet Connection</span>
    </div>
</div>

{{-- ── Desktop Navbar ── --}}
<nav class="rc-navbar d-none d-md-block">
    <div class="d-flex align-items-center justify-content-between">
        <a class="rc-navbar-brand" href="{{ route('dashboard') }}">
            <div class="brand-icon"><i class="fa-solid fa-person-running"></i></div>
            {{ $brandName ?? 'RunConnect' }}
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('dashboard.profile') }}" class="rc-username">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->username, 0, 1)) }}</div>
                {{ Auth::user()->username }}
            </a>
            <form action="{{ route('logout') }}" method="POST" class="d-inline"
                onsubmit="localStorage.removeItem('runconnect_auth')">
                @csrf
                <button type="submit" class="btn rc-logout-btn">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ── Mobile Bottom Navigation ── --}}
<nav class="rc-bottom-nav d-md-none">
    <div class="rc-bottom-nav-inner">
        <a href="{{ route('dashboard') }}"
            class="rc-nav-tab {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('dashboard.profile') }}"
            class="rc-nav-tab {{ request()->routeIs('dashboard.profile') ? 'active' : '' }}">
            <i class="fa-solid fa-user-circle"></i>
            <span>Profile</span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="m-0 p-0"
            onsubmit="localStorage.removeItem('runconnect_auth')">
            @csrf
            <button type="submit" class="rc-nav-tab rc-nav-logout">
                <i class="fa-solid fa-right-from-bracket" style="color: var(--rc-danger);"></i>
                <span style="color: var(--rc-text-dim);">Logout</span>
            </button>
        </form>
    </div>
</nav>

{{-- ── Main Content ── --}}
<main class="rc-main">
    @hasSection('headerTitle')
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1 class="h3 fw-bold mb-0" style="color: var(--rc-text);">@yield('headerTitle')</h1>
            @yield('header-actions')
        </div>
    @endif

    {{-- Success Toast --}}
    @if(session('success'))
        <div class="alert rc-session-alert success alert-dismissible fade show mb-4" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Error Toast --}}
    @if(session('error'))
        <div class="alert rc-session-alert danger alert-dismissible fade show mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert rc-session-alert danger alert-dismissible fade show mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><strong>Please fix the following:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

<script>
    // ── Offline Toast ─────────────────────
    function showOfflineAlert() {
        const t = document.getElementById('offlineToast');
        if (t) { t.classList.add('show'); clearTimeout(window._offTimer); window._offTimer = setTimeout(hideOfflineAlert, 4000); }
    }
    function hideOfflineAlert() {
        const t = document.getElementById('offlineToast');
        if (t) t.classList.remove('show');
    }
    window.addEventListener('online',  hideOfflineAlert);
    window.addEventListener('offline', showOfflineAlert);

    // ── Block form submissions when offline ──
    document.addEventListener('submit', function(e) {
        if (!navigator.onLine) { e.preventDefault(); showOfflineAlert(); }
    });

    // ── Instant splash removal ────────────
    const splash = document.getElementById('splash-screen');
    if (splash) splash.remove();
</script>

@stack('scripts')
</body>
</html>
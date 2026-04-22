@include('partials.head')

<style>
/* ═════════════════════════════════════════════════════
   GUEST / AUTH LAYOUT — Mobile-First Dark Athletic
   Feels like a native app on phones, elegant card on desktop
═════════════════════════════════════════════════════ */

/* Splash reset */
#splash-screen { display: flex; }

/* ── Auth wrapper ─────────────────────────── */
.auth-wrapper {
    min-height: 100dvh;
    background: var(--rc-bg);
    display: flex;
    align-items: stretch;
}

/* Keep auth layout stable even if utility CSS is delayed */
.auth-wrapper .row {
    display: flex;
    justify-content: center;
    width: 100%;
}

.auth-col {
    width: 100%;
    max-width: 520px;
}

/* ── Auth card ────────────────────────────── */
.auth-card {
    background: var(--rc-surface);
    border: 1px solid var(--rc-border);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: none;
    transition: all 0.3s ease;
}

/* ── Auth header ──────────────────────────── */
.auth-header {
    background: linear-gradient(160deg, #0F2619 0%, #0D1117 100%);
    padding: 2.5rem 2rem 2rem;
    border-bottom: 1px solid var(--rc-border);
    position: relative;
    overflow: hidden;
    text-align: center;
}

.auth-header::before {
    content: '';
    position: absolute;
    top: -40px;
    right: -40px;
    width: 180px;
    height: 180px;
    background: radial-gradient(circle, var(--rc-green-glow) 0%, transparent 70%);
    pointer-events: none;
}

.auth-header::after {
    content: '';
    position: absolute;
    bottom: -30px;
    left: -30px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(0,210,106,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.auth-logo-icon {
    width: 68px;
    height: 68px;
    background: linear-gradient(135deg, var(--rc-green), #00A854);
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.9rem;
    color: #0D1117;
    box-shadow: 0 8px 32px var(--rc-green-glow);
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.auth-header h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--rc-text);
    letter-spacing: 0.5px;
    margin-bottom: 0.35rem;
    position: relative;
    z-index: 1;
}

.auth-header p {
    font-size: 0.85rem;
    color: var(--rc-text-muted);
    margin-bottom: 0;
    position: relative;
    z-index: 1;
}

/* ── Auth body ────────────────────────────── */
.auth-body {
    padding: 2rem;
    background: var(--rc-surface);
}

/* ── Form Controls (dark style) ───────────── */
.auth-body .form-label {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: var(--rc-text-muted);
    margin-bottom: 0.4rem;
}

.auth-body .form-control,
.auth-body .form-select {
    background: var(--rc-surface-2) !important;
    border: 1.5px solid var(--rc-border) !important;
    border-radius: 12px !important;
    color: var(--rc-text) !important;
    padding: 0.75rem 1rem !important;
    font-size: 0.95rem !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
}

.auth-body .form-control::placeholder { color: var(--rc-text-dim) !important; }

.auth-body .form-control:focus,
.auth-body .form-select:focus {
    background: var(--rc-surface-2) !important;
    border-color: var(--rc-green) !important;
    box-shadow: 0 0 0 4px var(--rc-green-glow) !important;
    color: var(--rc-text) !important;
}

/* ── Input Groups ────────────────────────── */
.auth-body .input-group {
    display: flex;
    align-items: stretch;
    width: 100%;
    border-radius: 12px !important;
    overflow: hidden;
}

.auth-body .input-group > .form-control,
.auth-body .input-group > .form-select {
    flex: 1 1 auto;
    min-width: 0;
    border-radius: 0 !important;
}

.auth-body .input-group:focus-within {
    box-shadow: 0 0 0 4px var(--rc-green-glow);
    border-radius: 12px;
}

.auth-body .input-group:focus-within .form-control,
.auth-body .input-group:focus-within .input-group-text {
    border-color: var(--rc-green) !important;
}

.auth-body .input-group-text {
    display: inline-flex;
    align-items: center;
    background: var(--rc-surface-2) !important;
    border: 1.5px solid var(--rc-border) !important;
    color: var(--rc-text-muted) !important;
    border-radius: 0 !important;
}

.auth-body .input-group .btn {
    background: var(--rc-surface-2) !important;
    border: 1.5px solid var(--rc-border) !important;
    border-left: none !important;
    color: var(--rc-text-muted) !important;
    border-radius: 0 !important;
}

.auth-body .input-group .btn:hover { color: var(--rc-text) !important; }

/* ── Checkbox ────────────────────────────── */
.auth-body .form-check-input {
    background-color: var(--rc-surface-2);
    border-color: var(--rc-border);
}
.auth-body .form-check-input:checked {
    background-color: var(--rc-green);
    border-color: var(--rc-green);
}
.auth-body .form-check-label { color: var(--rc-text-muted); font-size: 0.85rem; }

/* ── Alert ───────────────────────────────── */
.auth-body .alert-danger {
    background: rgba(248, 81, 73, 0.12) !important;
    border: 1px solid rgba(248, 81, 73, 0.3) !important;
    color: #f85149 !important;
    border-radius: 12px !important;
}

.auth-body .alert-success {
    background: rgba(0, 210, 106, 0.1) !important;
    border: 1px solid rgba(0, 210, 106, 0.25) !important;
    color: var(--rc-green) !important;
    border-radius: 12px !important;
}

/* ── Divider ─────────────────────────────── */
.auth-divider {
    text-align: center;
    position: relative;
    color: var(--rc-text-dim);
    font-size: 0.78rem;
    letter-spacing: 0.5px;
    margin: 1.25rem 0;
}
.auth-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--rc-border);
}
.auth-divider span { background: var(--rc-surface); padding: 0 0.75rem; position: relative; z-index: 1; }

/* ── Links ───────────────────────────────── */
.auth-body a { color: var(--rc-green) !important; text-decoration: none !important; }
.auth-body a:hover { color: #00B85D !important; text-decoration: underline !important; }

/* ── Badge ───────────────────────────────── */
.auth-body .badge.bg-primary {
    background: var(--rc-green) !important;
    color: #0D1117 !important;
    border-radius: 8px !important;
}

/* ── Range slider ────────────────────────── */
.auth-body .form-range::-webkit-slider-thumb {
    background: var(--rc-green);
    box-shadow: 0 0 8px var(--rc-green-glow);
}
.auth-body .form-range::-webkit-slider-runnable-track { background: var(--rc-surface-3); }

/* ── Back link ───────────────────────────── */
.auth-back-link {
    font-size: 0.8rem;
    color: var(--rc-text-muted) !important;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s ease;
}
.auth-back-link:hover { color: var(--rc-text) !important; }

/* ── Step badge ──────────────────────────── */
.step-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: var(--rc-green);
    color: #0D1117;
    border-radius: 8px;
    font-weight: 800;
    font-size: 0.8rem;
    margin-right: 0.5rem;
}

/* ── Offline Toast ───────────────────────── */
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

/* ── Mobile Fullscreen ───────────────────── */
@media (max-width: 767.98px) {
    .auth-wrapper {
        align-items: flex-start !important;
        padding: 0 !important;
    }

    .auth-col {
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }

    .auth-card {
        border-radius: 0 !important;
        border: none !important;
        box-shadow: none !important;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
    }

    .auth-header {
        border-radius: 0 0 28px 28px !important;
        padding: 2.5rem 1.5rem 2rem !important;
    }

    .auth-header .auth-logo-icon { width: 60px; height: 60px; font-size: 1.6rem; border-radius: 18px; }
    .auth-header h3 { font-size: 1.35rem; }

    .auth-body {
        flex: 1;
        padding: 1.75rem 1.5rem 2rem !important;
        overflow-y: auto;
    }

    /* Sticky submit button on mobile */
    .auth-sticky-btn {
        position: sticky;
        bottom: 1rem;
    }
}
</style>
</head>

<body>

{{-- ── Splash Screen ── --}}
<div id="splash-screen">
    <div class="splash-ring"></div>
    <div class="splash-ring"></div>
    <div class="splash-ring"></div>
    <div class="splash-icon">
        <i class="fa-solid fa-person-running"></i>
    </div>
    <div class="splash-text">RUNCONNECT</div>
    <div class="splash-tagline">Your Running Journey Starts Here</div>
    <div class="splash-loader">
        <div class="splash-loader-bar"></div>
    </div>
</div>

{{-- ── Offline Toast ── --}}
<div class="rc-toast" id="offlineToast">
    <div class="rc-toast-inner">
        <i class="fa-solid fa-wifi-slash"></i>
        <span>No Internet Connection</span>
    </div>
</div>

{{-- ── Auth Wrapper ── --}}
<div class="container-fluid auth-wrapper py-4 py-md-5">
    <div class="row w-100 justify-content-center m-0">
        <div class="col-md-6 col-lg-5 col-xl-4 auth-col p-2 p-md-0">

            <div class="auth-card">

                {{-- ── Header ── --}}
                <div class="auth-header">
                    {{-- Desktop back link --}}
                    <a href="/" class="auth-back-link position-absolute top-0 start-0 m-3 d-none d-md-inline-flex">
                        <i class="fa-solid fa-chevron-left"></i> Home
                    </a>

                    <div class="auth-logo-icon">
                        <i class="fa-solid fa-person-running"></i>
                    </div>
                    <h3>@yield('title', 'RunConnect')</h3>
                    <p>@yield('subtitle', 'Your running journey starts here')</p>
                </div>

                {{-- ── Body ── --}}
                <div class="auth-body">
                    @yield('content')
                    <div class="text-center mt-4">
                        @yield('footer-link')
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // ── Offline Toast Logic ──────────────────
    function showOfflineAlert() {
        const t = document.getElementById('offlineToast');
        if (t) { t.classList.add('show'); clearTimeout(window._offlineTimer); window._offlineTimer = setTimeout(hideOfflineAlert, 4000); }
    }
    function hideOfflineAlert() {
        const t = document.getElementById('offlineToast');
        if (t) t.classList.remove('show');
    }
    window.addEventListener('online', hideOfflineAlert);
    window.addEventListener('offline', showOfflineAlert);

    // ── Hide Splash Screen ───────────────────
    window.addEventListener('load', () => {
        const splash = document.getElementById('splash-screen');
        if (!splash) return;
        setTimeout(() => {
            splash.style.transition = 'opacity 0.6s ease, visibility 0.6s';
            splash.style.opacity = '0';
            splash.style.visibility = 'hidden';
            setTimeout(() => splash.remove(), 700);
        }, 1800);
    });
</script>

@stack('scripts')
</body>
</html>
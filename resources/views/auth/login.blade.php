@extends('layouts.guest')

@section('title', 'Welcome Back')
@section('subtitle', 'Sign in to continue your run')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger py-2 small rounded-3 mb-4">
            @foreach ($errors->all() as $error)
                <div><i class="fa-solid fa-circle-exclamation me-1"></i> {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        {{-- Username / Email --}}
        <div class="mb-3">
            <label class="form-label">Username or Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" name="login" id="login" required autofocus
                    placeholder="Enter username or email" value="{{ old('login') }}">
            </div>
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control border-end-0" name="password" id="password"
                    required placeholder="Enter your password">
                <button class="btn" type="button" id="togglePassword">
                    <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        {{-- Remember + Forgot --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" id="forgotPasswordLink"
                    class="small fw-semibold" style="color: var(--rc-green) !important;">Forgot Password?</a>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-4 auth-sticky-btn">
            <button type="submit" class="btn btn-primary" id="loginBtn">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Sign In
            </button>
        </div>

        {{-- Divider --}}
        <div class="auth-divider"><span>Don't have an account?</span></div>

        {{-- Register link --}}
        <div class="text-center mt-3">
            <a href="{{ route('register') }}" id="registerLink"
                class="d-inline-flex align-items-center gap-2 fw-bold"
                style="color: var(--rc-green) !important; font-size: 0.95rem;">
                <i class="fa-solid fa-user-plus"></i> Create Account
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // --- Toggle Password Visibility ---
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });

    // --- Login Form Submission with connection check ---
    document.getElementById('loginForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!navigator.onLine) { showOfflineAlert(); return; }

        const btn = document.getElementById('loginBtn');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Signing in...';
        btn.disabled = true;

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 6000);

        try {
            const res = await fetch(window.location.href, {
                method: 'HEAD', cache: 'no-store', signal: controller.signal
            });
            clearTimeout(timeout);
            if (res.ok || res.status === 401 || res.status === 419) {
                this.submit();
            } else { throw new Error(); }
        } catch {
            clearTimeout(timeout);
            showOfflineAlert();
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    });

    // --- Intercept links if offline ---
    ['forgotPasswordLink', 'registerLink'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', e => { if (!navigator.onLine) { e.preventDefault(); showOfflineAlert(); } });
    });
</script>
@endpush
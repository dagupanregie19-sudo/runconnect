@extends('layouts.guest')

@section('title', 'Reset Password')
@section('subtitle', 'We\'ll send a reset link to your email')

@section('content')
    @if (session('status'))
        <div class="alert alert-success py-3 small rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i> {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 small rounded-3 mb-4">
            @foreach ($errors->all() as $error)
                <div><i class="fa-solid fa-circle-exclamation me-1"></i> {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Info Banner --}}
    <div class="d-flex align-items-start gap-3 p-3 mb-4 rounded-3"
        style="background: rgba(0,210,106,0.06); border: 1px solid rgba(0,210,106,0.15);">
        <i class="fa-solid fa-shield-halved mt-1" style="color: var(--rc-green); font-size: 1.1rem;"></i>
        <div>
            <div class="fw-semibold" style="color: var(--rc-text); font-size: 0.9rem;">Account Recovery</div>
            <div style="color: var(--rc-text-muted); font-size: 0.8rem; line-height: 1.5;">
                Enter the email address linked to your RunConnect account. We'll send a secure reset link.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('password.email') }}" id="resetForm">
        @csrf

        <div class="mb-4">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                    id="email" name="email" value="{{ old('email') }}"
                    required autocomplete="email" autofocus placeholder="name@example.com">
                @error('email')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>

        <div class="d-grid auth-sticky-btn">
            <button type="submit" class="btn btn-primary" id="resetBtn">
                <i class="fa-solid fa-paper-plane me-2"></i> Send Reset Link
            </button>
        </div>
    </form>
@endsection

@section('footer-link')
    <a href="{{ route('login') }}" class="auth-back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
    </a>
@endsection

@push('scripts')
<script>
    document.getElementById('resetForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!navigator.onLine) { showOfflineAlert(); return; }

        const btn = document.getElementById('resetBtn');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Sending...';
        btn.disabled = true;

        try {
            const res = await fetch(window.location.href, { method: 'HEAD', cache: 'no-store' });
            if (res.ok || res.status === 401 || res.status === 419) {
                this.submit();
            } else { throw new Error(); }
        } catch {
            showOfflineAlert();
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    });
</script>
@endpush
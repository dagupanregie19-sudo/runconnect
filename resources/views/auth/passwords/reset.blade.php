@extends('layouts.guest')

@section('title', 'Set New Password')
@section('subtitle', 'Choose a strong password')

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label fw-bold small text-muted text-uppercase">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i
                        class="fa-regular fa-envelope"></i></span>
                <input type="email" class="form-control border-start-0 ps-0 bg-light @error('email') is-invalid @enderror"
                    id="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                    placeholder="name@example.com">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-bold small text-muted text-uppercase">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                <input type="password"
                    class="form-control border-start-0 border-end-0 ps-0 bg-light @error('password') is-invalid @enderror"
                    id="password" name="password" required autocomplete="new-password">
                <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePassword">
                    <i class="fa-regular fa-eye"></i>
                </button>
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <!-- Password Requirements List -->
            <div class="mt-2" id="password-requirements">
                <small class="d-block text-muted mb-1 fw-bold">Password must contain:</small>
                <ul class="list-unstyled small text-muted ps-1 mb-0">
                    <li id="req-length"><i class="fa-regular fa-circle me-1"></i> At least 8 characters</li>
                    <li id="req-upper"><i class="fa-regular fa-circle me-1"></i> At least one uppercase letter</li>
                    <li id="req-number"><i class="fa-regular fa-circle me-1"></i> At least one number</li>
                    <li id="req-special"><i class="fa-regular fa-circle me-1"></i> At least one special character (@$!%*?&)
                    </li>
                </ul>
            </div>
        </div>

        <div class="mb-3">
            <label for="password-confirm" class="form-label fw-bold small text-muted text-uppercase">Confirm
                Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control border-start-0 border-end-0 ps-0 bg-light" id="password-confirm"
                    name="password_confirmation" required autocomplete="new-password">
                <button class="btn btn-light border border-start-0 text-muted" type="button" id="toggleConfirmPassword">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">
                Reset Password <i class="fa-solid fa-check ms-2"></i>
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const toggleIcon = togglePasswordBtn.querySelector('i');

            // Requirements Elements
            const requirements = {
                length: document.getElementById('req-length'),
                upper: document.getElementById('req-upper'),
                number: document.getElementById('req-number'),
                special: document.getElementById('req-special')
            };

            const confirmPasswordInput = document.getElementById('password-confirm');
            const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
            const toggleConfirmIcon = toggleConfirmPasswordBtn.querySelector('i');

            // Toggle Password Visibility
            togglePasswordBtn.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle Icon
                if (type === 'text') {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            });

            // Toggle Confirm Password Visibility
            toggleConfirmPasswordBtn.addEventListener('click', function () {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);

                // Toggle Icon
                if (type === 'text') {
                    toggleConfirmIcon.classList.remove('fa-eye');
                    toggleConfirmIcon.classList.add('fa-eye-slash');
                } else {
                    toggleConfirmIcon.classList.remove('fa-eye-slash');
                    toggleConfirmIcon.classList.add('fa-eye');
                }
            });

            // Live Validation
            passwordInput.addEventListener('input', function () {
                const value = this.value;

                // Validation Rules
                const rules = {
                    length: value.length >= 8,
                    upper: /[A-Z]/.test(value),
                    number: /[0-9]/.test(value),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(value)
                };

                // Update UI
                for (const [key, element] of Object.entries(requirements)) {
                    const icon = element.querySelector('i');
                    if (rules[key]) {
                        element.classList.remove('text-muted');
                        element.classList.add('text-success');
                        icon.classList.remove('fa-circle', 'fa-regular');
                        icon.classList.add('fa-circle-check', 'fa-solid');
                    } else {
                        element.classList.remove('text-success');
                        element.classList.add('text-muted');
                        icon.classList.remove('fa-circle-check', 'fa-solid');
                        icon.classList.add('fa-circle', 'fa-regular');
                    }
                }
            });
        });
    </script>
@endsection
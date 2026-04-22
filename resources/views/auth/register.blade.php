@extends('layouts.guest')

@section('title', 'Join RunConnect')
@section('subtitle', 'Start your journey today!')

@section('content')
    <style>
        /* ── Role Selection Buttons ── */
        .rc-role-btn {
            background: var(--rc-surface-2);
            border: 1.5px solid var(--rc-border);
            color: var(--rc-text-muted);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-check:checked + .rc-role-btn {
            background: rgba(0,210,106,0.1);
            border-color: var(--rc-green);
            color: var(--rc-green);
            box-shadow: 0 0 12px var(--rc-green-glow);
        }
        .btn-check:focus + .rc-role-btn,
        .rc-role-btn:hover {
            border-color: rgba(0,210,106,0.5);
            color: var(--rc-text);
        }

        /* ── Password Reqs ── */
        .password-requirements li { transition: color 0.3s ease; }
        .req-icon { transition: all 0.3s ease; }
        .req-success { color: var(--rc-green) !important; }
        .req-danger { color: #f85149 !important; }
    </style>

    <form id="registerForm" method="POST" action="{{ route('register') }}">
        @csrf

        {{-- ── Step 1: Username & Role ── --}}
        <div id="step1">
            <h5 class="fw-bold mb-4" style="color: var(--rc-text);"><span class="step-indicator">1</span> Choose Username</h5>

            <div class="mb-4">
                <label class="form-label">I want to join as a:</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="role" id="role_runner" value="user" checked>
                        <label class="btn rc-role-btn w-100 fw-bold py-2 rounded-3" for="role_runner">
                            <i class="fa-solid fa-person-running me-2"></i> Runner
                        </label>
                    </div>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="role" id="role_organizer" value="organizer">
                        <label class="btn rc-role-btn w-100 fw-bold py-2 rounded-3" for="role_organizer">
                            <i class="fa-solid fa-calendar-check me-2"></i> Organizer
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0"><i class="fa-solid fa-at"></i></span>
                    <input type="text" class="form-control border-start-0 border-end-0 ps-0 shadow-none"
                        id="username" name="username" required placeholder="Choose a unique username">
                    <button class="btn border-start-0" type="button" id="checkUsernameBtn" style="border-radius: 0 12px 12px 0 !important; color: var(--rc-green); font-weight: 600;">Check</button>
                </div>
                <div id="usernameFeedback" class="form-text small mt-2"></div>
            </div>

            <button type="button" class="btn btn-primary w-100 fw-bold auth-sticky-btn" id="nextStep1" disabled>
                Next <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </div>

        {{-- ── Step 2: Email Verification ── --}}
        <div id="step2" class="d-none">
            <h5 class="fw-bold mb-4" style="color: var(--rc-text);"><span class="step-indicator">2</span> Verify Email</h5>

            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" required
                        placeholder="name@example.com">
                </div>
                <button class="btn btn-outline-light btn-sm w-100 mt-3 py-2 border-1" type="button" id="sendCodeBtn" style="border-radius: 10px !important;">
                    Send Verification Code
                </button>
                <div id="emailFeedback" class="form-text small mt-2 text-center"></div>
                <small id="timer" class="d-block text-center mt-1" style="color: var(--rc-text-muted); font-weight: 600;"></small>
            </div>

            <div class="mb-4" id="otpSection" style="display:none; background: rgba(0,210,106,0.05); border: 1px solid rgba(0,210,106,0.15); border-radius: 12px; padding: 1rem;">
                <label for="verification_code" class="form-label text-center d-block">Enter 6-Digit Code</label>
                <input type="text" class="form-control text-center fw-bold" id="verification_code"
                    name="verification_code" maxlength="6" placeholder="______" style="letter-spacing: 0.5rem; font-size: 1.2rem;">
                <button class="btn btn-primary w-100 mt-3 fw-bold" type="button" id="verifyCodeBtn">
                    Verify & Continue
                </button>
                <div id="verifyFeedback" class="form-text small mt-2 text-center"></div>
            </div>

            <div class="d-flex gap-2 auth-sticky-btn">
                <button type="button" class="btn btn-outline-light w-100" id="prevStep2">Back</button>
            </div>
        </div>

        {{-- ── Step 3: Password ── --}}
        <div id="step3" class="d-none">
            <h5 class="fw-bold mb-4" style="color: var(--rc-text);"><span class="step-indicator">3</span> Secure Account</h5>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" required>
                    <button class="btn border-start-0 text-muted" type="button" id="togglePasswordBtn">
                        <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
                
                {{-- Dynamic Requirements --}}
                <div class="password-requirements mt-3 p-3 rounded-3" style="background: var(--rc-surface-2);">
                    <ul class="list-unstyled mb-0" style="font-size: 0.82rem; font-weight: 600;">
                        <li id="req-length" class="req-danger mb-1"><i class="fa-solid fa-circle req-icon me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>8+ characters</li>
                        <div id="advanced-reqs">
                            <li id="req-upper" class="req-danger mb-1"><i class="fa-solid fa-circle req-icon me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>Uppercase letter</li>
                            <li id="req-number" class="req-danger mb-1"><i class="fa-solid fa-circle req-icon me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>Number</li>
                            <li id="req-special" class="req-danger"><i class="fa-solid fa-circle req-icon me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>Special char (@$!%*#?&)</li>
                        </div>
                    </ul>
                </div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password_confirmation" name="password_confirmation" required>
                    <button class="btn border-start-0 text-muted" type="button" id="toggleConfirmPasswordBtn">
                        <i class="fa-regular fa-eye" id="toggleConfirmPasswordIcon"></i>
                    </button>
                </div>
                <small id="matchFeedback" class="d-none fw-bold mt-2" style="color: #f85149;">Passwords do not match</small>
            </div>

            <div class="d-flex gap-2 auth-sticky-btn">
                <button type="button" class="btn btn-outline-light" style="width: 30%;" id="prevStep3">Back</button>
                <button type="submit" class="btn btn-primary flex-grow-1 fw-bold" id="submitBtn" disabled>
                    Create Account <i class="fa-solid fa-check ms-2"></i>
                </button>
            </div>
        </div>

        {{-- Divider --}}
        <div class="auth-divider mt-4"><span>Already have an account?</span></div>

        {{-- Login link --}}
        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="d-inline-flex align-items-center gap-2 fw-bold" style="color: var(--rc-green) !important; font-size: 0.95rem;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Back to Login
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('#registerForm input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    function buildPostOptions(payload) {
        return {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        };
    }

    // Step Navigation Variables
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');

    // Step 1 Logic: Username Check
    const usernameInput = document.getElementById('username');
    const checkUsernameBtn = document.getElementById('checkUsernameBtn');
    const nextStep1Btn = document.getElementById('nextStep1');
    const usernameFeedback = document.getElementById('usernameFeedback');

    checkUsernameBtn.addEventListener('click', async () => {
        if (!navigator.onLine) { showOfflineAlert(); return; }

        const username = usernameInput.value.replace(/^@/, '').trim();
        if (!username) return;
        usernameInput.value = username;

        const originalText = checkUsernameBtn.innerHTML;
        checkUsernameBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        checkUsernameBtn.disabled = true;

        try {
            const response = await fetch('{{ route("register.check-username") }}', buildPostOptions({ username }));

            if (response.ok) {
                usernameFeedback.innerHTML = '<span style="color: var(--rc-green);"><i class="fa-solid fa-check-circle me-1"></i> Username available!</span>';
                nextStep1Btn.disabled = false;
            } else {
                const data = await response.json();
                usernameFeedback.innerHTML = `<span style="color: #f85149;"><i class="fa-solid fa-circle-xmark me-1"></i> ${data.message || 'Username taken.'}</span>`;
                nextStep1Btn.disabled = true;
            }
        } catch (e) {
            usernameFeedback.innerHTML = `<span style="color: #f85149;">Network error.</span>`;
        } finally {
            checkUsernameBtn.innerHTML = originalText;
            checkUsernameBtn.disabled = false;
        }
    });

    nextStep1Btn.addEventListener('click', () => { step1.classList.add('d-none'); step2.classList.remove('d-none'); });

    // Step 2 Logic: Email & OTP
    const emailInput = document.getElementById('email');
    const sendCodeBtn = document.getElementById('sendCodeBtn');
    const otpSection = document.getElementById('otpSection');
    const timerElement = document.getElementById('timer');
    const verifyCodeBtn = document.getElementById('verifyCodeBtn');
    const verificationInput = document.getElementById('verification_code');
    const prevStep2Btn = document.getElementById('prevStep2');
    const emailFeedback = document.getElementById('emailFeedback');
    const verifyFeedback = document.getElementById('verifyFeedback');

    prevStep2Btn.addEventListener('click', () => { step2.classList.add('d-none'); step1.classList.remove('d-none'); });

    let timerInterval, timeLeft = 180;
    function startTimer() {
        sendCodeBtn.disabled = true; timeLeft = 180;
        timerElement.innerText = `Resend in ${Math.floor(timeLeft/60)}:${(timeLeft%60).toString().padStart(2,'0')}`;
        clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            timeLeft--;
            timerElement.innerText = `Resend in ${Math.floor(timeLeft/60)}:${(timeLeft%60).toString().padStart(2,'0')}`;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                sendCodeBtn.disabled = false;
                sendCodeBtn.innerText = 'Resend Verification Code';
                timerElement.innerText = '';
            }
        }, 1000);
    }

    sendCodeBtn.addEventListener('click', async () => {
        if (!navigator.onLine) { showOfflineAlert(); return; }
        const email = emailInput.value; emailFeedback.innerHTML = '';
        if (!email) { emailFeedback.innerHTML = '<span style="color: #f85149;">Please enter an email address.</span>'; return; }

        const origText = sendCodeBtn.innerHTML;
        sendCodeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Sending...';
        sendCodeBtn.disabled = true;

        try {
            const res = await fetch('{{ route("register.send-code") }}', buildPostOptions({ email }));
            const data = await res.json();
            if (res.ok) {
                emailFeedback.innerHTML = `<span style="color: var(--rc-green);"><i class="fa-solid fa-check mx-1"></i>${data.message}</span>`;
                otpSection.style.display = 'block';
                startTimer();
            } else {
                let err = data.message || 'Error sending code.';
                if (data.errors && data.errors.email) err = data.errors.email[0];
                emailFeedback.innerHTML = `<span style="color: #f85149;">${err}</span>`;
                sendCodeBtn.innerHTML = origText; sendCodeBtn.disabled = false;
            }
        } catch (e) {
            emailFeedback.innerHTML = `<span style="color: #f85149;">Network error.</span>`;
            sendCodeBtn.innerHTML = origText; sendCodeBtn.disabled = false;
        }
    });

    verifyCodeBtn.addEventListener('click', async () => {
        if (!navigator.onLine) { showOfflineAlert(); return; }
        const code = verificationInput.value; verifyFeedback.innerHTML = '';
        const origText = verifyCodeBtn.innerHTML;
        verifyCodeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Verifying...';
        verifyCodeBtn.disabled = true;

        try {
            const res = await fetch('{{ route("register.verify-code") }}', buildPostOptions({ code }));
            if (res.ok) {
                step2.classList.add('d-none');
                step3.classList.remove('d-none');
                validatePassword();
            } else {
                const data = await res.json();
                let err = data.message || 'Invalid Code';
                if (data.errors && data.errors.code) err = data.errors.code[0];
                verifyFeedback.innerHTML = `<span style="color: #f85149;">${err}</span>`;
                verifyCodeBtn.innerHTML = origText; verifyCodeBtn.disabled = false;
            }
        } catch (e) {
            verifyFeedback.innerHTML = `<span style="color: #f85149;">Network error.</span>`;
            verifyCodeBtn.innerHTML = origText; verifyCodeBtn.disabled = false;
        }
    });

    // Step 3 Logic: Password Val
    const pw = document.getElementById('password');
    const cpw = document.getElementById('password_confirmation');
    const submitBtn = document.getElementById('submitBtn');
    const matchFeedback = document.getElementById('matchFeedback');
    document.getElementById('prevStep3').addEventListener('click', () => { step3.classList.add('d-none'); step2.classList.remove('d-none'); });

    function updateReq(id, valid) {
        const el = document.getElementById(id);
        const icon = el.querySelector('i');
        if (valid) {
            el.classList.remove('req-danger'); el.classList.add('req-success');
            icon.classList.remove('fa-circle'); icon.classList.add('fa-check');
        } else {
            el.classList.remove('req-success'); el.classList.add('req-danger');
            icon.classList.remove('fa-check'); icon.classList.add('fa-circle');
        }
    }

    function validatePassword() {
        const role = document.querySelector('input[name="role"]:checked')?.value || 'user';
        const val = pw.value;
        const hasLength = val.length >= 8;
        updateReq('req-length', hasLength);

        let isValid = hasLength;
        const adv = document.getElementById('advanced-reqs');
        
        if (role === 'organizer') {
            adv.style.display = 'block';
            const hasUpper = /[A-Z]/.test(val), hasNum = /[0-9]/.test(val), hasSpec = /[@$!%*#?&]/.test(val);
            updateReq('req-upper', hasUpper); updateReq('req-number', hasNum); updateReq('req-special', hasSpec);
            isValid = hasLength && hasUpper && hasNum && hasSpec;
        } else { adv.style.display = 'none'; }

        const isMatch = val === cpw.value && val !== '';
        if (!isMatch && cpw.value !== '') matchFeedback.classList.remove('d-none');
        else matchFeedback.classList.add('d-none');

        submitBtn.disabled = !(isValid && isMatch);
    }
    pw.addEventListener('input', validatePassword); cpw.addEventListener('input', validatePassword);

    // Toggle Password Visibility
    ['Password', 'ConfirmPassword'].forEach(t => {
        document.getElementById(`toggle${t}Btn`).addEventListener('click', () => {
            const input = document.getElementById(t === 'Password' ? 'password' : 'password_confirmation');
            const icon = document.getElementById(`toggle${t}Icon`);
            const isPw = input.type === 'password';
            input.type = isPw ? 'text' : 'password';
            icon.className = isPw ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    });

    // Form Submit
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault(); if (!navigator.onLine) { showOfflineAlert(); return; }
        const orig = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creating...'; submitBtn.disabled = true;
        try {
            const res = await fetch(window.location.href, { method: 'HEAD', cache: 'no-store' });
            if (res.ok || res.status===401 || res.status===419) {
                usernameInput.value = usernameInput.value.replace(/^@/, '').trim(); this.submit();
            } else throw new Error();
        } catch { showOfflineAlert(); submitBtn.innerHTML = orig; submitBtn.disabled = false; }
    });
</script>
@endpush
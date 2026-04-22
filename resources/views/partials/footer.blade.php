<footer style="background: var(--rc-surface); border-top: 1px solid var(--rc-border); padding: 3.5rem 0 2rem;">
    <div class="container">
        <div class="row g-4 justify-content-between">
            {{-- Brand Column --}}
            <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div style="width: 34px; height: 34px; background: linear-gradient(135deg, var(--rc-green), #00A854); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; color: #0D1117;">
                        <i class="fa-solid fa-person-running"></i>
                    </div>
                    <span style="font-weight: 800; font-size: 1rem; letter-spacing: 1px; color: var(--rc-text); text-transform: uppercase;">RunConnect</span>
                </div>
                <p style="color: var(--rc-text-muted); font-size: 0.85rem; line-height: 1.7; max-width: 320px;">
                    The ultimate marathon management platform. Seamless registration, real-time tracking, and community engagement.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; border-radius: 10px; background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text-muted); text-decoration: none; transition: all 0.2s ease;"
                        onmouseover="this.style.background='var(--rc-green-glow)'; this.style.color='var(--rc-green)'; this.style.borderColor='rgba(0,210,106,0.3)';"
                        onmouseout="this.style.background='var(--rc-surface-2)'; this.style.color='var(--rc-text-muted)'; this.style.borderColor='var(--rc-border)';">
                        <i class="fab fa-facebook-f" style="font-size: 0.85rem;"></i>
                    </a>
                    <a href="#" class="d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; border-radius: 10px; background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text-muted); text-decoration: none; transition: all 0.2s ease;"
                        onmouseover="this.style.background='var(--rc-green-glow)'; this.style.color='var(--rc-green)'; this.style.borderColor='rgba(0,210,106,0.3)';"
                        onmouseout="this.style.background='var(--rc-surface-2)'; this.style.color='var(--rc-text-muted)'; this.style.borderColor='var(--rc-border)';">
                        <i class="fab fa-twitter" style="font-size: 0.85rem;"></i>
                    </a>
                    <a href="#" class="d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; border-radius: 10px; background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text-muted); text-decoration: none; transition: all 0.2s ease;"
                        onmouseover="this.style.background='var(--rc-green-glow)'; this.style.color='var(--rc-green)'; this.style.borderColor='rgba(0,210,106,0.3)';"
                        onmouseout="this.style.background='var(--rc-surface-2)'; this.style.color='var(--rc-text-muted)'; this.style.borderColor='var(--rc-border)';">
                        <i class="fab fa-instagram" style="font-size: 0.85rem;"></i>
                    </a>
                </div>
            </div>

            {{-- Runners Column --}}
            <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                <h6 style="font-size: 0.72rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--rc-text); margin-bottom: 1rem;">Runners</h6>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--rc-text-muted); font-size: 0.85rem; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--rc-green)';" onmouseout="this.style.color='var(--rc-text-muted)';">Events</a></li>
                    <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--rc-text-muted); font-size: 0.85rem; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--rc-green)';" onmouseout="this.style.color='var(--rc-text-muted)';">Leaderboard</a></li>
                    <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--rc-text-muted); font-size: 0.85rem; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--rc-green)';" onmouseout="this.style.color='var(--rc-text-muted)';">Training Plans</a></li>
                </ul>
            </div>

            {{-- Support Column --}}
            <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                <h6 style="font-size: 0.72rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--rc-text); margin-bottom: 1rem;">Support</h6>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--rc-text-muted); font-size: 0.85rem; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--rc-green)';" onmouseout="this.style.color='var(--rc-text-muted)';">FAQ</a></li>
                    <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--rc-text-muted); font-size: 0.85rem; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--rc-green)';" onmouseout="this.style.color='var(--rc-text-muted)';">Contact Us</a></li>
                    <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--rc-text-muted); font-size: 0.85rem; transition: color 0.2s;"
                        onmouseover="this.style.color='var(--rc-green)';" onmouseout="this.style.color='var(--rc-text-muted)';">Terms & Privacy</a></li>
                </ul>
            </div>

            {{-- Get the App Column --}}
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h6 style="font-size: 0.72rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--rc-text); margin-bottom: 1rem;">Get the App</h6>
                <p style="color: var(--rc-text-muted); font-size: 0.82rem; margin-bottom: 1rem;">Install our PWA for the best mobile experience.</p>
                <button class="btn w-100 mb-2 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2"
                    style="background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text); font-size: 0.85rem; padding: 0.6rem;">
                    <i class="fab fa-apple"></i> App Store
                </button>
                <button class="btn w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2"
                    style="background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text); font-size: 0.85rem; padding: 0.6rem;">
                    <i class="fab fa-google-play"></i> Play Store
                </button>
            </div>
        </div>

        {{-- Copyright --}}
        <div style="border-top: 1px solid var(--rc-border); padding-top: 1.5rem; margin-top: 2rem;" class="text-center">
            <p style="color: var(--rc-text-dim); font-size: 0.78rem; margin-bottom: 0;">
                &copy; {{ date('Y') }} RunConnect. Secure & Verified.
            </p>
        </div>
    </div>
</footer>

<script>
    // Splash Screen Logic
    document.addEventListener('DOMContentLoaded', () => {
        const splashScreen = document.getElementById('splash-screen');
        const mainContent = document.getElementById('main-content');

        if (splashScreen && mainContent) {
            if (splashScreen.dataset.redirecting === "true") return;

            setTimeout(() => {
                splashScreen.style.transition = 'opacity 0.6s ease, visibility 0.6s';
                splashScreen.style.opacity = '0';
                splashScreen.style.visibility = 'hidden';
                mainContent.style.opacity = '1';
                setTimeout(() => splashScreen.remove(), 700);
            }, 2000);
        }
    });
</script>
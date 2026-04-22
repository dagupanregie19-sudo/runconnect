@include('partials.head')

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

    {{-- ── Main Content ── --}}
    <div id="main-content" style="opacity: 0; transition: opacity 0.8s ease-in;">

        @include('partials.nav')

        {{-- ═══ Hero Section ═══ --}}
        <div class="container-fluid py-5" style="padding-top: 5rem !important; padding-bottom: 5rem !important;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 text-lg-start text-center mb-5 mb-lg-0">
                        <h1 class="display-3 fw-900 mb-4" style="line-height: 1.1;">
                            <span style="color: var(--rc-text);">Streamline Your</span><br>
                            <span class="hero-title">Marathon Journey</span>
                        </h1>
                        <p class="lead mb-4" style="color: var(--rc-text-muted); font-size: 1.1rem; line-height: 1.7; max-width: 480px;">
                            Official registration app for global marathon events. Secure, responsive, and built for runners.
                        </p>
                        <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg" style="font-size: 0.95rem;">
                                Start Registering <i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                            <a href="#features" class="btn btn-lg"
                                style="background: transparent; border: 1.5px solid var(--rc-border); color: var(--rc-text-muted); border-radius: 14px; padding: 0.875rem 1.5rem; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease;"
                                onmouseover="this.style.borderColor='rgba(0,210,106,0.4)'; this.style.color='var(--rc-green)';"
                                onmouseout="this.style.borderColor='var(--rc-border)'; this.style.color='var(--rc-text-muted)';">
                                Learn More
                            </a>
                        </div>

                        {{-- Stats --}}
                        <div class="mt-5 d-flex align-items-center justify-content-center justify-content-lg-start gap-4">
                            <div class="text-start">
                                <h3 class="fw-bold mb-0" style="color: var(--rc-green);">50K+</h3>
                                <small style="color: var(--rc-text-muted); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Active Runners</small>
                            </div>
                            <div style="width: 1px; height: 40px; background: var(--rc-border);"></div>
                            <div class="text-start">
                                <h3 class="fw-bold mb-0" style="color: var(--rc-green);">120+</h3>
                                <small style="color: var(--rc-text-muted); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Global Events</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1452626038306-9aae5e071dd3?q=80&w=2074&auto=format&fit=crop"
                                alt="Marathon Runner" class="img-fluid position-relative z-1"
                                style="max-height: 500px; object-fit: cover; width: 100%; border-radius: 24px; border: 1px solid var(--rc-border);">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Features Section ═══ --}}
        <div class="container py-5 my-5" id="features">
            <div class="text-center mb-5">
                <h5 style="color: var(--rc-green); font-size: 0.78rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 0.75rem;">
                    WHY CHOOSE RUNCONNECT?
                </h5>
                <h2 class="fw-bold display-6" style="color: var(--rc-text);">Elevate Your Running Experience</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 rounded-4 overflow-hidden" style="background: var(--rc-surface); border: 1px solid var(--rc-border) !important; transition: all 0.3s ease;"
                        onmouseover="this.style.borderColor='rgba(0,210,106,0.25)'; this.style.transform='translateY(-4px)';"
                        onmouseout="this.style.borderColor='var(--rc-border)'; this.style.transform='translateY(0)';">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mx-auto">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <h4 class="card-title fw-bold mb-3" style="color: var(--rc-text);">Instant Registration</h4>
                            <p class="card-text" style="color: var(--rc-text-muted); font-size: 0.9rem; line-height: 1.6;">
                                Lightning-fast signup process with secure payment integration and instant confirmation.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 rounded-4 overflow-hidden" style="background: var(--rc-surface); border: 1px solid var(--rc-border) !important; transition: all 0.3s ease;"
                        onmouseover="this.style.borderColor='rgba(0,210,106,0.25)'; this.style.transform='translateY(-4px)';"
                        onmouseout="this.style.borderColor='var(--rc-border)'; this.style.transform='translateY(0)';">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mx-auto">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <h4 class="card-title fw-bold mb-3" style="color: var(--rc-text);">Live Tracking</h4>
                            <p class="card-text" style="color: var(--rc-text-muted); font-size: 0.9rem; line-height: 1.6;">
                                Real-time GPS tracking for friends and family to follow your progress on race day.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 rounded-4 overflow-hidden" style="background: var(--rc-surface); border: 1px solid var(--rc-border) !important; transition: all 0.3s ease;"
                        onmouseover="this.style.borderColor='rgba(0,210,106,0.25)'; this.style.transform='translateY(-4px)';"
                        onmouseout="this.style.borderColor='var(--rc-border)'; this.style.transform='translateY(0)';">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon mx-auto">
                                <i class="fa-solid fa-medal"></i>
                            </div>
                            <h4 class="card-title fw-bold mb-3" style="color: var(--rc-text);">Digital Certificates</h4>
                            <p class="card-text" style="color: var(--rc-text-muted); font-size: 0.9rem; line-height: 1.6;">
                                Receive verifiable digital achievement badges and certificates instantly upon finishing.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Featured Events ═══ --}}
        <div class="container py-5" id="events">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0" style="color: var(--rc-text);">Featured Marathons</h3>
                <a href="#" style="color: var(--rc-green); text-decoration: none; font-weight: 600; font-size: 0.9rem;"
                    onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                    View All <i class="fa-solid fa-arrow-right ms-1" style="font-size: 0.8rem;"></i>
                </a>
            </div>
            <div class="row g-4">
                {{-- Event Card 1 --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 rounded-4 overflow-hidden h-100" style="background: var(--rc-surface); border: 1px solid var(--rc-border) !important; transition: all 0.3s ease;"
                        onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(0,210,106,0.2)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--rc-border)';">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&q=80&w=800"
                                class="card-img-top" alt="City Marathon" style="height: 200px; object-fit: cover; opacity: 0.85;">
                            <span class="position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill fw-bold"
                                style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); color: var(--rc-text); font-size: 0.78rem; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="fa-regular fa-calendar me-1"></i> Oct 15, 2026
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold" style="color: var(--rc-text);">Metropolis City Run</h5>
                            <p class="card-text small" style="color: var(--rc-text-muted);">
                                <i class="fa-solid fa-location-dot me-1" style="color: var(--rc-green);"></i> New York, USA
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <span class="fw-bold" style="color: var(--rc-green); font-size: 1.05rem;">$45.00</span>
                                <a href="#" class="btn btn-sm rounded-pill px-4"
                                    style="background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text); font-size: 0.82rem; font-weight: 600; transition: all 0.2s ease;"
                                    onmouseover="this.style.borderColor='var(--rc-green)'; this.style.color='var(--rc-green)';"
                                    onmouseout="this.style.borderColor='var(--rc-border)'; this.style.color='var(--rc-text)';">
                                    Register
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Event Card 2 --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 rounded-4 overflow-hidden h-100" style="background: var(--rc-surface); border: 1px solid var(--rc-border) !important; transition: all 0.3s ease;"
                        onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(0,210,106,0.2)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--rc-border)';">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?auto=format&fit=crop&q=80&w=800"
                                class="card-img-top" alt="Trail Marathon" style="height: 200px; object-fit: cover; opacity: 0.85;">
                            <span class="position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill fw-bold"
                                style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); color: var(--rc-text); font-size: 0.78rem; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="fa-regular fa-calendar me-1"></i> Nov 02, 2026
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold" style="color: var(--rc-text);">Highland Trail Ultra</h5>
                            <p class="card-text small" style="color: var(--rc-text-muted);">
                                <i class="fa-solid fa-location-dot me-1" style="color: var(--rc-green);"></i> Denver, USA
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <span class="fw-bold" style="color: var(--rc-green); font-size: 1.05rem;">$60.00</span>
                                <a href="#" class="btn btn-sm rounded-pill px-4"
                                    style="background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text); font-size: 0.82rem; font-weight: 600; transition: all 0.2s ease;"
                                    onmouseover="this.style.borderColor='var(--rc-green)'; this.style.color='var(--rc-green)';"
                                    onmouseout="this.style.borderColor='var(--rc-border)'; this.style.color='var(--rc-text)';">
                                    Register
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Event Card 3 --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 rounded-4 overflow-hidden h-100" style="background: var(--rc-surface); border: 1px solid var(--rc-border) !important; transition: all 0.3s ease;"
                        onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(0,210,106,0.2)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--rc-border)';">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1516726817505-f5ed825624d8?auto=format&fit=crop&q=80&w=800"
                                class="card-img-top" alt="Coastal Run" style="height: 200px; object-fit: cover; opacity: 0.85;">
                            <span class="position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill fw-bold"
                                style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); color: var(--rc-text); font-size: 0.78rem; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="fa-regular fa-calendar me-1"></i> Dec 10, 2026
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold" style="color: var(--rc-text);">Pacific Coast Marathon</h5>
                            <p class="card-text small" style="color: var(--rc-text-muted);">
                                <i class="fa-solid fa-location-dot me-1" style="color: var(--rc-green);"></i> California, USA
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <span class="fw-bold" style="color: var(--rc-green); font-size: 1.05rem;">$55.00</span>
                                <a href="#" class="btn btn-sm rounded-pill px-4"
                                    style="background: var(--rc-surface-2); border: 1px solid var(--rc-border); color: var(--rc-text); font-size: 0.82rem; font-weight: 600; transition: all 0.2s ease;"
                                    onmouseover="this.style.borderColor='var(--rc-green)'; this.style.color='var(--rc-green)';"
                                    onmouseout="this.style.borderColor='var(--rc-border)'; this.style.color='var(--rc-text)';">
                                    Register
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ CTA Section ═══ --}}
        <div class="container my-5" id="community">
            <div class="rounded-5 p-5 text-center position-relative overflow-hidden"
                style="background: linear-gradient(135deg, #0A2E1A 0%, #0D3A22 50%, #0F1E15 100%); border: 1px solid rgba(0,210,106,0.15);">
                <div class="position-relative z-1">
                    <h2 class="fw-bold mb-3" style="color: var(--rc-text); font-size: 2rem;">Ready to Hit the Ground Running?</h2>
                    <p class="lead mb-4" style="color: var(--rc-text-muted); font-size: 1.05rem;">
                        Join thousands of runners and start your journey today. <br>The finish line awaits.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold">
                        Start Registering
                    </a>
                </div>
                {{-- Decorative Glow --}}
                <div class="position-absolute rounded-circle"
                    style="top: -60px; left: -60px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(0,210,106,0.12), transparent 70%);"></div>
                <div class="position-absolute rounded-circle"
                    style="bottom: -80px; right: -80px; width: 350px; height: 350px; background: radial-gradient(circle, rgba(0,210,106,0.08), transparent 70%);"></div>
            </div>
        </div>

        <script>
            // Mobile / PWA Detection & Redirect
            document.addEventListener('DOMContentLoaded', () => {
                const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone || document.referrer.includes('android-app://');
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                const splashScreen = document.getElementById('splash-screen');

                if (isPWA || (isMobile && window.innerWidth < 768)) {
                    if (splashScreen) splashScreen.dataset.redirecting = "true";

                    let targetUrl = "{{ Auth::check() ? route('dashboard') : route('login') }}";
                    if (localStorage.getItem('runconnect_auth') === 'true') {
                        targetUrl = "{{ route('dashboard') }}";
                    }

                    setTimeout(() => { window.location.href = targetUrl; }, 2000);
                }
            });
        </script>

        @include('partials.footer')
</body>
</html>
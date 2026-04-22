<nav class="navbar navbar-expand-lg sticky-top py-3" style="background: rgba(13,17,23,0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.06);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="{{ url('/') }}">
            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, var(--rc-green), #00A854); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #0D1117;">
                <i class="fa-solid fa-person-running"></i>
            </div>
            <span style="font-weight: 900; font-size: 1.1rem; letter-spacing: 1px; color: var(--rc-text); text-transform: uppercase;">RunConnect</span>
        </a>

        <div class="d-flex ms-auto gap-2 align-items-center">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary rounded-pill px-4 fw-bold"
                    style="height: 42px; display: flex; align-items: center; justify-content: center; font-size: 0.88rem;">
                    Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn rounded-pill px-4 fw-bold ms-1"
                        style="height: 42px; display: flex; align-items: center; justify-content: center; font-size: 0.88rem; background: rgba(248,81,73,0.12); border: 1px solid rgba(248,81,73,0.3); color: #f85149;">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                    class="btn rounded-pill px-4 fw-bold d-inline-flex align-items-center justify-content-center"
                    style="height: 42px; background: transparent; border: 1.5px solid rgba(255,255,255,0.15); color: var(--rc-text-muted); font-size: 0.88rem; transition: all 0.2s ease;"
                    onmouseover="this.style.borderColor='rgba(0,210,106,0.5)'; this.style.color='var(--rc-green)';"
                    onmouseout="this.style.borderColor='rgba(255,255,255,0.15)'; this.style.color='var(--rc-text-muted)';">
                    Login
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="btn btn-primary rounded-pill px-4 fw-bold d-inline-flex align-items-center justify-content-center"
                        style="height: 42px; font-size: 0.88rem;">
                        Register
                    </a>
                @endif
            @endauth
        </div>
    </div>
</nav>
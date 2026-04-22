<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — RunConnect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            background: #0c1120;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #f8fafc;
            margin: 0;
            overflow: hidden;
        }

        /* ── Animated gradient orbs ────────────────────────── */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.12;
            animation: orbFloat 12s ease-in-out infinite alternate;
            z-index: 0;
        }
        .bg-orb--1 {
            width: 500px; height: 500px;
            background: #1aad6e;
            top: -120px; left: -100px;
            animation-duration: 14s;
        }
        .bg-orb--2 {
            width: 400px; height: 400px;
            background: #0e6e3e;
            bottom: -80px; right: -60px;
            animation-duration: 10s;
            animation-delay: 2s;
        }
        .bg-orb--3 {
            width: 250px; height: 250px;
            background: #22d3ee;
            top: 50%; left: 55%;
            opacity: 0.06;
            animation-duration: 16s;
            animation-delay: 4s;
        }
        @keyframes orbFloat {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -40px) scale(1.15); }
        }

        /* ── Main container ────────────────────────────────── */
        .error-page {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem 1.5rem;
            max-width: 520px;
            width: 100%;
        }

        /* ── Brand ─────────────────────────────────────────── */
        .error-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 2.5rem;
        }
        .error-brand-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0e6e3e, #1aad6e);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 1rem;
        }
        .error-brand-text {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.85);
        }

        /* ── Icon circle ───────────────────────────────────── */
        .error-icon-ring {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(26, 173, 110, 0.08);
            border: 2px solid rgba(26, 173, 110, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            animation: pulse-ring 3s ease-in-out infinite;
        }
        .error-icon-ring i {
            font-size: 2.2rem;
            background: linear-gradient(135deg, #0e6e3e, #1aad6e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(26,173,110,0.15); }
            50%      { box-shadow: 0 0 0 18px rgba(26,173,110,0); }
        }

        /* ── Error code ────────────────────────────────────── */
        .error-code {
            font-size: 5.5rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.4rem;
            background: linear-gradient(135deg, #1aad6e 0%, #22d3ee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
        }

        /* ── Message & description ─────────────────────────── */
        .error-message {
            font-size: 1.35rem;
            font-weight: 700;
            color: rgba(255,255,255, 0.92);
            margin-bottom: 0.6rem;
        }
        .error-description {
            color: rgba(255,255,255, 0.45);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2.2rem;
            max-width: 380px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Action buttons ────────────────────────────────── */
        .error-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .btn-home {
            background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 14px 36px;
            font-weight: 700;
            font-size: 0.92rem;
            letter-spacing: 0.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(26, 173, 110, 0.25);
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(26, 173, 110, 0.35);
            color: #fff;
        }
        .btn-back {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255, 0.5);
            border-radius: 50px;
            padding: 11px 28px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.75);
            border-color: rgba(255,255,255,0.2);
        }

        /* ── Decorative divider ─────────────────────────────── */
        .error-divider {
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #0e6e3e, #1aad6e);
            border-radius: 3px;
            margin: 0 auto 1.8rem;
        }

        /* ── Responsive ────────────────────────────────────── */
        @media (max-width: 480px) {
            .error-code { font-size: 4rem; }
            .error-message { font-size: 1.15rem; }
            .error-icon-ring { width: 80px; height: 80px; }
            .error-icon-ring i { font-size: 1.8rem; }
        }
    </style>
</head>

<body>
    <!-- Animated background blobs -->
    <div class="bg-orb bg-orb--1"></div>
    <div class="bg-orb bg-orb--2"></div>
    <div class="bg-orb bg-orb--3"></div>

    <div class="error-page">
        <!-- Brand -->
        <div class="error-brand">
            <div class="error-brand-icon">
                <i class="fa-solid fa-person-running"></i>
            </div>
            <span class="error-brand-text">RunConnect</span>
        </div>

        <!-- Animated icon ring -->
        <div class="error-icon-ring">
            <i class="fa-solid @yield('icon', 'fa-triangle-exclamation')"></i>
        </div>

        <!-- Error code -->
        <div class="error-code">@yield('code')</div>

        <!-- Green gradient divider -->
        <div class="error-divider"></div>

        <!-- Message + description -->
        <h1 class="error-message">@yield('message')</h1>
        <p class="error-description">@yield('description')</p>

        <!-- Action buttons -->
        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn-home">
                <i class="fa-solid fa-house"></i> Go Home
            </a>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
</body>

</html>
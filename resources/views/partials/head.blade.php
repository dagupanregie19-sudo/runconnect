<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'RunConnect') }}</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#00D26A">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icon.svg">
    <link rel="icon" type="image/svg+xml" href="/icon.svg">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Fonts: Inter + Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════════════
           RUNCONNECT DESIGN SYSTEM v2.0
           Dark Athletic Theme — Native Mobile App Feel
        ═══════════════════════════════════════════════════════ */
        :root {
            --rc-green: #00D26A;
            --rc-green-dim: #00A854;
            --rc-green-glow: rgba(0, 210, 106, 0.25);
            --rc-green-subtle: rgba(0, 210, 106, 0.08);
            --rc-bg: #0D1117;
            --rc-surface: #161B22;
            --rc-surface-2: #21262D;
            --rc-surface-3: #30363D;
            --rc-border: rgba(255,255,255,0.08);
            --rc-text: #E6EDF3;
            --rc-text-muted: #7D8590;
            --rc-text-dim: #484F58;
            --rc-danger: #F85149;
            --rc-warning: #E3B341;
            --rc-blue: #388BFD;

            /* Bootstrap Override */
            --bs-primary: #00D26A;
            --bs-primary-rgb: 0, 210, 106;
            --bs-link-color: #00D26A;
            --bs-link-hover-color: #00A854;
            --bs-body-bg: #0D1117;
            --bs-body-color: #E6EDF3;
        }

        * { -webkit-tap-highlight-color: transparent; }

        html, body {
            font-family: 'Inter', 'Outfit', sans-serif;
            background-color: var(--rc-bg);
            color: var(--rc-text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Ensure inactive tabs do not consume invisible layout space */
        .tab-pane:not(.active) { display: none !important; }

        /* ── Splash Screen ─────────────────────────────────── */
        #splash-screen {
            position: fixed;
            inset: 0;
            background: var(--rc-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
            gap: 1.5rem;
        }

        #splash-screen .splash-ring {
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 2px solid var(--rc-green);
            opacity: 0.15;
            animation: splash-pulse 2s ease-out infinite;
        }
        #splash-screen .splash-ring:nth-child(2) { animation-delay: 0.5s; width: 230px; height: 230px; }
        #splash-screen .splash-ring:nth-child(3) { animation-delay: 1s; width: 280px; height: 280px; }

        @keyframes splash-pulse {
            0%   { transform: scale(0.85); opacity: 0.25; }
            50%  { transform: scale(1.05); opacity: 0.08; }
            100% { transform: scale(0.85); opacity: 0.25; }
        }

        .splash-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--rc-green), #00B894);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #fff;
            box-shadow: 0 0 40px var(--rc-green-glow), 0 0 80px rgba(0,210,106,0.1);
            animation: icon-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            z-index: 1;
        }

        @keyframes icon-pop {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .splash-text {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 3px;
            color: var(--rc-text);
            opacity: 0;
            animation: fade-up 0.6s ease-out 0.4s forwards;
            z-index: 1;
        }

        .splash-tagline {
            font-size: 0.85rem;
            color: var(--rc-text-muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: 0;
            animation: fade-up 0.6s ease-out 0.7s forwards;
            z-index: 1;
        }

        .splash-loader {
            width: 120px;
            height: 3px;
            background: var(--rc-surface-2);
            border-radius: 99px;
            overflow: hidden;
            z-index: 1;
            margin-top: 0.5rem;
        }

        .splash-loader-bar {
            height: 100%;
            background: var(--rc-green);
            border-radius: 99px;
            animation: loader-fill 1.8s ease-in-out forwards;
            box-shadow: 0 0 12px var(--rc-green-glow);
        }

        @keyframes loader-fill {
            from { width: 0%; }
            to   { width: 100%; }
        }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Global Utility ────────────────────────────────── */
        .btn-primary {
            background: var(--rc-green) !important;
            border-color: var(--rc-green) !important;
            color: #0D1117 !important;
            font-weight: 700 !important;
            letter-spacing: 0.3px;
            border-radius: 14px !important;
            padding: 0.875rem 1.5rem !important;
            transition: all 0.25s ease !important;
            box-shadow: 0 4px 20px var(--rc-green-glow) !important;
        }

        .btn-primary:hover, .btn-primary:focus {
            background: #00B85D !important;
            border-color: #00B85D !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 28px var(--rc-green-glow) !important;
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-outline-light {
            border-radius: 14px !important;
            padding: 0.875rem 1.5rem !important;
            font-weight: 600 !important;
        }

        /* Hero Section (Landing) */
        .hero-section {
            padding: 5rem 1rem;
            text-align: center;
        }

        .hero-title {
            font-weight: 900;
            background: linear-gradient(135deg, var(--rc-green), #00B894);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-icon {
            font-size: 1.75rem;
            color: var(--rc-green);
            background: var(--rc-green-subtle);
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            display: inline-flex;
            transition: transform 0.3s ease;
        }

        .card:hover .feature-icon { transform: rotate(8deg) scale(1.1); }

        @media (max-width: 576px) {
            .hero-section { padding: 3rem 1rem; }
            .hero-title { font-size: 2.2rem; }
        }
    </style>

    <script>
        if ('serviceWorker' in navigator) {
            const isLocalDevHost = ['localhost', '127.0.0.1', 'runconnect.test'].includes(window.location.hostname);

            if (isLocalDevHost) {
                navigator.serviceWorker.getRegistrations()
                    .then(registrations => {
                        registrations.forEach(reg => reg.unregister());
                    })
                    .catch(() => {});
            }

            window.addEventListener('load', () => {
                if (isLocalDevHost) {
                    return;
                }

                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(() => console.log('SW registered'))
                    .catch(() => console.log('SW registration failed'));
            });
        }
    </script>
</head>
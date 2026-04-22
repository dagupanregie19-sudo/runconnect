@extends('layouts.dashboard')

@section('sidebar')
    <li class="nav-item">
        <a class="nav-link active fw-bold text-primary" href="{{ route('dashboard') }}">
            <i class="fa-solid fa-gauge-high me-2"></i> Admin Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="#">
            <i class="fa-solid fa-users me-2"></i> Users Management
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="#">
            <i class="fa-solid fa-calendar-alt me-2"></i> Events
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="#">
            <i class="fa-solid fa-chart-bar me-2"></i> Analytics
        </a>
    </li>
    <li class="nav-item mt-3 border-top pt-3">
        <a class="nav-link text-muted hover-primary" href="{{ route('dashboard.settings') }}">
            <i class="fa-solid fa-cogs me-2"></i> System Settings
        </a>
    </li>
@endsection

@section('headerTitle', 'Admin Dashboard')
@section('header-actions')
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<style>
    .hybrid-select { position: relative; }
    .hybrid-input { cursor: text; }
    .hybrid-input::placeholder { font-size: 0.78rem; }
    .hybrid-dropdown {
        display: none; position: absolute; z-index: 1055;
        background: var(--rc-surface, #1a1d23); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 0 0 .375rem .375rem;
        max-height: 180px; overflow-y: auto; width: 100%;
        box-shadow: 0 6px 16px rgba(0,0,0,.4);
    }
    .hybrid-dropdown.show { display: block; }
    .hybrid-dropdown .hd-item {
        padding: 5px 10px; cursor: pointer; font-size: .8rem;
        border-bottom: 1px solid rgba(255,255,255,0.05); transition: background .15s;
        color: rgba(255,255,255,0.7);
    }
    .hybrid-dropdown .hd-item:hover { background: rgba(26,173,110,0.15); }
    .hybrid-dropdown .hd-item .hd-match { font-weight: 700; color: #1aad6e; }
    .hybrid-dropdown .hd-empty {
        padding: 8px 10px; color: rgba(255,255,255,0.4); font-size: .8rem; font-style: italic;
    }
    html.map-fullscreen-active,
    body.map-fullscreen-active {
        overflow: hidden !important;
        height: 100% !important;
    }
    .map-fullscreen {
        position: fixed !important;
        inset: 0;
        width: 100vw !important; height: 100dvh !important;
        z-index: 1060 !important;
        border-radius: 0 !important;
        margin: 0 !important;
        max-width: none !important;
        max-height: none !important;
        background: var(--rc-surface, #0d1117);
    }
    @supports not (height: 100dvh) {
        .map-fullscreen { height: 100vh !important; }
    }
    .map-fullscreen .leaflet-control-zoom {
        display: none !important;
    }
    .map-fs-toolbar {
        position: absolute;
        top: 12px;
        left: 12px;
        right: 150px;
        z-index: 10000;
        display: flex;
        gap: 8px;
        align-items: stretch;
        pointer-events: none;
    }
    .map-fs-toolbar .map-fs-input {
        pointer-events: auto;
        min-width: 200px;
        flex: 1 1 auto;
        background-color: #ffffff !important;
        color: #212529 !important;
        border: 1px solid #adb5bd !important;
        border-radius: 0.375rem;
        box-shadow: none;
    }
    .map-fs-toolbar .map-fs-btn {
        pointer-events: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        min-width: 42px;
    }
    @media (max-width: 767.98px) {
        .map-fs-toolbar {
            top: 62px;
            left: 12px;
            right: 12px;
            flex-wrap: wrap;
        }
        .map-fs-toolbar .map-fs-input {
            min-width: 0;
            flex: 1 1 100%;
        }
        .map-fs-toolbar .map-fs-btn {
            flex: 1 1 calc(25% - 6px);
            min-height: 40px;
        }
    }

    /* Admin Dashboard Dark Theme */
    #adminTabs { border-bottom: 2px solid rgba(255,255,255,0.05); }
    #adminTabs .nav-link {
        color: rgba(255,255,255,0.5);
        border: none;
        border-bottom: 2px solid transparent;
        padding: 12px 20px;
        margin-bottom: -2px;
        transition: all 0.2s;
        border-radius: 0 !important;
        background: transparent;
    }
    #adminTabs .nav-link:hover {
        color: rgba(255,255,255,0.8);
        border-bottom-color: rgba(255,255,255,0.2);
    }
    #adminTabs .nav-link.active {
        background: transparent !important;
        color: #1aad6e !important;
        border-bottom-color: #1aad6e !important;
    }

    .admin-stat-card {
        background: var(--rc-surface-2, #161b22);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        padding: 20px;
        transition: transform 0.18s, box-shadow 0.18s;
    }
    .admin-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }

    .admin-dark-card {
        background: var(--rc-surface-2, #161b22) !important;
        border: 1px solid rgba(255,255,255,0.07) !important;
    }
    .admin-dark-card .card-header {
        background: transparent !important;
        border-bottom: 1px solid rgba(255,255,255,0.07) !important;
    }

    .admin-table {
        color: rgba(255,255,255,0.75);
    }
    .admin-table thead tr {
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .admin-table thead th {
        background: rgba(255,255,255,0.03);
        color: rgba(255,255,255,0.45);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border: none;
    }
    .admin-table tbody tr {
        border-bottom: 1px solid rgba(255,255,255,0.05);
        transition: background 0.15s;
    }
    .admin-table tbody tr:hover {
        background: rgba(255,255,255,0.03);
    }
    .admin-table tbody td {
        padding: 14px 16px;
        border: none;
        vertical-align: middle;
    }
    .admin-tabs-scroll {
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none;  /* IE and Edge */
    }
    .admin-tabs-scroll::-webkit-scrollbar {
        display: none; /* Chrome, Safari and Opera */
    }
    .admin-tabs-scroll .nav-item {
        white-space: nowrap;
    }
</style>

{{-- ═══ Admin Dashboard Tabs ═══ --}}
<ul class="nav nav-tabs mb-4 admin-tabs-scroll" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="admin-overview-tab" data-bs-toggle="tab"
            data-bs-target="#admin-overview" type="button" role="tab" aria-controls="admin-overview"
            aria-selected="true">
            <i class="fa-solid fa-house me-2"></i>Overview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="admin-users-tab" data-bs-toggle="tab"
            data-bs-target="#admin-users" type="button" role="tab" aria-controls="admin-users"
            aria-selected="false">
            <i class="fa-solid fa-users me-2"></i>Users Management
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="admin-my-events-tab" data-bs-toggle="tab"
            data-bs-target="#admin-my-events" type="button" role="tab" aria-controls="admin-my-events"
            aria-selected="false">
            <i class="fa-solid fa-calendar-alt me-2"></i>Events
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="admin-events-tab" data-bs-toggle="tab"
            data-bs-target="#admin-events" type="button" role="tab" aria-controls="admin-events"
            aria-selected="false">
            <i class="fa-solid fa-list-check me-2"></i>Event Management
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="admin-my-history-tab" data-bs-toggle="tab"
            data-bs-target="#admin-my-history" type="button" role="tab" aria-controls="admin-my-history"
            aria-selected="false">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>My History
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="admin-all-history-tab" data-bs-toggle="tab"
            data-bs-target="#admin-all-history" type="button" role="tab" aria-controls="admin-all-history"
            aria-selected="false">
            <i class="fa-solid fa-folder-open me-2"></i>All History
        </button>
    </li>
</ul>

<div class="tab-content" id="adminTabsContent">
    <!-- Overview Tab -->
    <div class="tab-pane fade show active" id="admin-overview" role="tabpanel" tabindex="0">

{{-- ═══ Stats Overview Cards ═══ --}}
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px; background: rgba(26,173,110,0.15); border: 1px solid rgba(26,173,110,0.25);">
                    <i class="fa-solid fa-calendar-day" style="color: #1aad6e; font-size: 1.1rem;"></i>
                </div>
                <div>
                    <div class="small fw-bold text-uppercase" style="color: rgba(255,255,255,0.4); font-size: 0.68rem; letter-spacing: 0.5px;">Total Events</div>
                    <div class="fw-bold" style="color: var(--rc-text); font-size: 1.5rem; line-height: 1.2;">{{ $statsJson['totalEvents'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px; background: rgba(13,202,240,0.12); border: 1px solid rgba(13,202,240,0.25);">
                    <i class="fa-solid fa-users" style="color: #0dcaf0; font-size: 1.1rem;"></i>
                </div>
                <div>
                    <div class="small fw-bold text-uppercase" style="color: rgba(255,255,255,0.4); font-size: 0.68rem; letter-spacing: 0.5px;">Total Users</div>
                    <div class="fw-bold" style="color: var(--rc-text); font-size: 1.5rem; line-height: 1.2;">{{ $statsJson['totalUsers'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px; background: rgba(255,193,7,0.12); border: 1px solid rgba(255,193,7,0.25);">
                    <i class="fa-solid fa-building" style="color: #ffc107; font-size: 1.1rem;"></i>
                </div>
                <div>
                    <div class="small fw-bold text-uppercase" style="color: rgba(255,255,255,0.4); font-size: 0.68rem; letter-spacing: 0.5px;">Organizers</div>
                    <div class="fw-bold" style="color: var(--rc-text); font-size: 1.5rem; line-height: 1.2;">{{ $statsJson['totalOrganizers'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px; background: rgba(168,85,247,0.12); border: 1px solid rgba(168,85,247,0.25);">
                    <i class="fa-solid fa-running" style="color: #a855f7; font-size: 1.1rem;"></i>
                </div>
                <div>
                    <div class="small fw-bold text-uppercase" style="color: rgba(255,255,255,0.4); font-size: 0.68rem; letter-spacing: 0.5px;">Total Runners</div>
                    <div class="fw-bold" style="color: var(--rc-text); font-size: 1.5rem; line-height: 1.2;">{{ $statsJson['totalRunners'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Charts & Analytics ═══ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

{{-- Row 1: Registration Trends (wide) + Event Status (narrow) --}}
<div class="row g-4 mb-4">
    {{-- Registration Trends Line/Area Chart --}}
    <div class="col-lg-8">
        <div class="admin-stat-card h-100" style="padding: 20px 20px 12px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1" style="color: var(--rc-text); font-size: 0.95rem;">
                        <i class="fa-solid fa-chart-line me-2" style="color: #1aad6e;"></i>Registration Trends
                    </h6>
                    <small style="color: rgba(255,255,255,0.35);">Last 6 months</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.4);">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #1aad6e; display: inline-block;"></span> Registrations
                    </span>
                </div>
            </div>
            <div style="position: relative; height: 240px;">
                <canvas id="adminRegTrendsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Event Status Doughnut --}}
    <div class="col-lg-4">
        <div class="admin-stat-card h-100" style="padding: 20px 20px 12px;">
            <h6 class="fw-bold mb-3" style="color: var(--rc-text); font-size: 0.95rem;">
                <i class="fa-solid fa-chart-pie me-2" style="color: #0dcaf0;"></i>Event Status
            </h6>
            <div style="position: relative; height: 200px;" class="d-flex align-items-center justify-content-center">
                <canvas id="adminStatusChart"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
                <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #0dcaf0; display: inline-block;"></span> Upcoming
                </span>
                <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #1aad6e; display: inline-block;"></span> Started
                </span>
                <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #6b7280; display: inline-block;"></span> Completed
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Row 2: Top Events (wide) + Difficulty Breakdown (narrow) --}}
<div class="row g-4 mb-4">
    {{-- Top Events Horizontal Bar --}}
    <div class="col-lg-7">
        <div class="admin-stat-card h-100" style="padding: 20px 20px 12px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1" style="color: var(--rc-text); font-size: 0.95rem;">
                        <i class="fa-solid fa-trophy me-2" style="color: #ffc107;"></i>Top Events
                    </h6>
                    <small style="color: rgba(255,255,255,0.35);">By registration count</small>
                </div>
            </div>
            <div style="position: relative; height: 220px;">
                <canvas id="adminTopEventsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Difficulty Breakdown Polar Area --}}
    <div class="col-lg-5">
        <div class="admin-stat-card h-100" style="padding: 20px 20px 12px;">
            <h6 class="fw-bold mb-3" style="color: var(--rc-text); font-size: 0.95rem;">
                <i class="fa-solid fa-signal me-2" style="color: #a855f7;"></i>Difficulty Breakdown
            </h6>
            <div style="position: relative; height: 220px;" class="d-flex align-items-center justify-content-center">
                <canvas id="adminDifficultyChart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Revenue/Registration breakdown + Quick Stats --}}
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="admin-stat-card h-100" style="padding: 20px;">
            <h6 class="fw-bold mb-3" style="color: var(--rc-text); font-size: 0.95rem;">
                <i class="fa-solid fa-wallet me-2" style="color: #f87171;"></i>Registration Breakdown
            </h6>
            <div style="position: relative; height: 200px;" class="d-flex align-items-center justify-content-center">
                <canvas id="adminRevenueChart"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
                <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #1aad6e; display: inline-block;"></span> Paid
                </span>
                <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #0dcaf0; display: inline-block;"></span> Free
                </span>
                <span class="d-flex align-items-center gap-1" style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #f87171; display: inline-block;"></span> Cancelled
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="admin-stat-card h-100" style="padding: 20px;">
            <h6 class="fw-bold mb-4" style="color: var(--rc-text); font-size: 0.95rem;">
                <i class="fa-solid fa-chart-column me-2" style="color: #1aad6e;"></i>Quick Stats
            </h6>
            <div class="row g-3">
                <div class="col-6">
                    <div class="rounded-3 p-3 text-center" style="background: rgba(26,173,110,0.08); border: 1px solid rgba(26,173,110,0.15);">
                        <div class="fw-bold" style="font-size: 1.6rem; color: #1aad6e;">₱{{ number_format($statsJson['totalRevenue'], 0) }}</div>
                        <div class="small fw-bold mt-1" style="color: rgba(255,255,255,0.4);">TOTAL REVENUE</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="rounded-3 p-3 text-center" style="background: rgba(13,202,240,0.08); border: 1px solid rgba(13,202,240,0.15);">
                        <div class="fw-bold" style="font-size: 1.6rem; color: #0dcaf0;">{{ $statsJson['totalSlots'] }}</div>
                        <div class="small fw-bold mt-1" style="color: rgba(255,255,255,0.4);">TOTAL SLOTS</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="rounded-3 p-3 text-center" style="background: rgba(255,193,7,0.08); border: 1px solid rgba(255,193,7,0.15);">
                        <div class="fw-bold" style="font-size: 1.6rem; color: #ffc107;">{{ $statsJson['paidCount'] }}</div>
                        <div class="small fw-bold mt-1" style="color: rgba(255,255,255,0.4);">PAID REGS</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="rounded-3 p-3 text-center" style="background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.15);">
                        <div class="fw-bold" style="font-size: 1.6rem; color: #f87171;">{{ $statsJson['cancelledCount'] }}</div>
                        <div class="small fw-bold mt-1" style="color: rgba(255,255,255,0.4);">CANCELLED</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart defaults for dark theme
    Chart.defaults.color = 'rgba(255,255,255,0.45)';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.display = false;

    // ── 1. Registration Trends (Area/Line Chart) ──
    const regCtx = document.getElementById('adminRegTrendsChart').getContext('2d');
    const regGradient = regCtx.createLinearGradient(0, 0, 0, 240);
    regGradient.addColorStop(0, 'rgba(26,173,110,0.3)');
    regGradient.addColorStop(1, 'rgba(26,173,110,0.0)');

    new Chart(regCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['monthlyLabels']) !!},
            datasets: [{
                data: {!! json_encode($chartData['monthlyRegs']) !!},
                borderColor: '#1aad6e',
                backgroundColor: regGradient,
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointBackgroundColor: '#1aad6e',
                pointBorderColor: '#0d1117',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#1aad6e',
                pointHoverBorderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { stepSize: 1, font: { size: 10 } }
                }
            },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,0.95)',
                    borderColor: 'rgba(26,173,110,0.3)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    cornerRadius: 10,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} registrations`
                    }
                }
            }
        }
    });

    // ── 2. Event Status Doughnut ──
    const statusData = {!! json_encode($chartData['statusDistribution']) !!};
    new Chart(document.getElementById('adminStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Upcoming', 'Started', 'Completed'],
            datasets: [{
                data: [statusData.upcoming, statusData.started, statusData.completed],
                backgroundColor: ['#0dcaf0', '#1aad6e', '#6b7280'],
                borderColor: 'rgba(13,17,23,0.8)',
                borderWidth: 3,
                hoverBorderColor: '#fff',
                hoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,0.95)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    cornerRadius: 10,
                    padding: 12,
                }
            }
        }
    });

    // ── 3. Top Events Horizontal Bar ──
    const topEventsData = {!! json_encode($chartData['topEvents']) !!};
    new Chart(document.getElementById('adminTopEventsChart'), {
        type: 'bar',
        data: {
            labels: topEventsData.map(e => e.name),
            datasets: [
                {
                    label: 'Registered',
                    data: topEventsData.map(e => e.count),
                    backgroundColor: 'rgba(26,173,110,0.7)',
                    borderColor: '#1aad6e',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6,
                },
                {
                    label: 'Total Slots',
                    data: topEventsData.map(e => e.slots),
                    backgroundColor: 'rgba(255,255,255,0.06)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6,
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { stepSize: 1, font: { size: 10 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        borderRadius: 3,
                        useBorderRadius: true,
                        padding: 12,
                        font: { size: 10 },
                        color: 'rgba(255,255,255,0.5)',
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,0.95)',
                    borderColor: 'rgba(26,173,110,0.3)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    cornerRadius: 10,
                    padding: 12,
                }
            }
        }
    });

    // ── 4. Difficulty Polar Area ──
    const diffData = {!! json_encode($chartData['difficultyDistribution']) !!};
    new Chart(document.getElementById('adminDifficultyChart'), {
        type: 'polarArea',
        data: {
            labels: Object.keys(diffData),
            datasets: [{
                data: Object.values(diffData),
                backgroundColor: [
                    'rgba(26,173,110,0.55)',
                    'rgba(13,202,240,0.55)',
                    'rgba(255,193,7,0.55)',
                    'rgba(248,113,113,0.55)',
                    'rgba(168,85,247,0.55)',
                ],
                borderColor: [
                    '#1aad6e',
                    '#0dcaf0',
                    '#ffc107',
                    '#f87171',
                    '#a855f7',
                ],
                borderWidth: 1.5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    angleLines: { color: 'rgba(255,255,255,0.06)' },
                    ticks: { display: false, stepSize: 1 },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        borderRadius: 3,
                        useBorderRadius: true,
                        padding: 10,
                        font: { size: 10 },
                        color: 'rgba(255,255,255,0.5)',
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,0.95)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    cornerRadius: 10,
                    padding: 12,
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed.r} events`
                    }
                }
            }
        }
    });

    // ── 5. Revenue Breakdown Doughnut ──
    const revData = {!! json_encode($chartData['revenueData']) !!};
    new Chart(document.getElementById('adminRevenueChart'), {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Free', 'Cancelled'],
            datasets: [{
                data: [revData.paid, revData.free, revData.cancelled],
                backgroundColor: ['#1aad6e', '#0dcaf0', '#f87171'],
                borderColor: 'rgba(13,17,23,0.8)',
                borderWidth: 3,
                hoverBorderColor: '#fff',
                hoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,0.95)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    cornerRadius: 10,
                    padding: 12,
                }
            }
        }
    });
});
</script>

    </div>

    <!-- Users Management Tab -->
    <div class="tab-pane fade" id="admin-users" role="tabpanel" tabindex="0">
        <div class="card admin-dark-card border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header p-4 pb-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: var(--rc-text);"><i class="fa-solid fa-users me-2" style="color: #1aad6e;"></i>Users Management</h5>
                        <p class="small mb-0" style="color: rgba(255,255,255,0.4);">Manage organizers and their associated events.</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($organizers->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-3 opacity-25"><i class="fa-solid fa-users-slash fa-3x" style="color: var(--rc-text-muted);"></i></div>
                        <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No organizers found.</h6>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Organizer</th>
                                    <th>Email</th>
                                    <th>Events</th>
                                    <th>Joined</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($organizers as $org)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px; background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.25); font-size: 0.85rem;">
                                                    {{ strtoupper(substr($org->username, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold" style="color: var(--rc-text);">{{ $org->username }}</div>
                                                    <div class="small" style="color: rgba(255,255,255,0.3);">ID: {{ $org->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="color: rgba(255,255,255,0.5);">{{ $org->email }}</td>
                                        <td>
                                            <span class="badge fw-bold rounded-pill px-3 py-1" style="background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.25);">{{ $org->events_count }} Events</span>
                                        </td>
                                        <td style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">{{ $org->created_at->format('M d, Y') }}</td>
                                        <td class="pe-4 text-end">
                                            <button type="button" class="btn btn-sm rounded-pill px-3" style="background: rgba(220,53,69,0.12); color: #f87171; border: 1px solid rgba(220,53,69,0.25);" onmouseover="this.style.background='rgba(220,53,69,0.25)'" onmouseout="this.style.background='rgba(220,53,69,0.12)'"
                                                onclick="confirmAdminDeleteUser({{ $org->id }}, '{{ addslashes($org->username) }}')" title="Delete Organizer">
                                                <i class="fa-solid fa-trash me-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Events Tab -->
    <div class="tab-pane fade" id="admin-my-events" role="tabpanel" tabindex="0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0" style="color: #1aad6e;">Events</h3>
            <button class="btn rounded-pill fw-bold shadow-sm px-4 py-2" data-bs-toggle="modal"
                data-bs-target="#adminCreateEventModal" style="background: linear-gradient(135deg, #1aad6e 0%, #0e6e3e 100%); color: white; border: none;">
                <i class="fa-solid fa-plus me-2"></i> Create Event
            </button>
        </div>

        @php
            $adminEvents = $events->where('organizer_id', auth()->id());
        @endphp

        @if($adminEvents->isEmpty())
            <div class="text-center py-5 rounded-4" style="background: var(--rc-surface-2); border: 1px dashed rgba(255,255,255,0.1);">
                <div class="mb-3 opacity-25"><i class="fa-solid fa-calendar-xmark fa-3x" style="color: var(--rc-text-muted);"></i></div>
                <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No events created yet.</h6>
                <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">Start by creating your first running event!</p>
            </div>
        @else
            @php
                $activeEvents = $adminEvents->where('status', '!=', 'completed');
            @endphp
            
            @if($activeEvents->isEmpty())
                <div class="text-center py-5 rounded-4" style="background: var(--rc-surface-2); border: 1px dashed rgba(255,255,255,0.1);">
                    <div class="mb-3 opacity-25"><i class="fa-solid fa-calendar-check fa-3x" style="color: var(--rc-text-muted);"></i></div>
                    <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No active events.</h6>
                    <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">Create a new running event!</p>
                </div>
            @else
                <div class="row g-4 mb-4">
                    @foreach($activeEvents as $event)
                        <div class="col-md-6 col-lg-4">
                            <div class="rc-event-card rounded-4 h-100 d-flex flex-column position-relative" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">

                                @if($event->status === 'started')
                                    <div class="position-absolute w-100 d-flex justify-content-center" style="top: -1px; z-index: 10;">
                                        <span class="badge bg-success fw-bold shadow-sm px-3 py-1" style="font-size: 0.68rem; letter-spacing: 0.5px; border-radius: 0 0 8px 8px;">
                                            <i class="fa-solid fa-satellite-dish me-1 spinner-grow spinner-grow-sm text-light align-middle" style="width: 8px; height: 8px;"></i> LIVE NOW
                                        </span>
                                    </div>
                                @endif

                                {{-- Gradient Header --}}
                                <div style="background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%); padding: 18px 20px 14px; position: relative;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 65%;">{{ $event->name }}</h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge fw-bold" style="background: rgba(255,255,255,0.2); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                                {{ $event->formatted_distance }}
                                            </span>
                                            <div class="dropdown">
                                                <button class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 30px; height: 30px; background: rgba(255,255,255,0.2); border: none;" type="button" data-bs-toggle="dropdown">
                                                    <i class="fa-solid fa-ellipsis-vertical text-white" style="font-size: 0.8rem;"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3" style="background: var(--rc-surface); border: 1px solid rgba(255,255,255,0.1) !important;">
                                                    <li>
                                                        <button class="dropdown-item small text-white" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="viewEventDetails({{ json_encode($event) }})">
                                                            <i class="fa-solid fa-eye me-2 text-primary"></i> View Details
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item small text-success fw-bold" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="openLiveMonitor({{ $event->id }}, '{{ addslashes($event->name) }}')">
                                                            <i class="fa-solid fa-map-location-dot me-2"></i> Live Monitor
                                                        </button>
                                                    </li>
                                                    <li>
                                                        @if($event->status === 'upcoming')
                                                            <button type="button" class="dropdown-item small text-success fw-bold" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="confirmAdminToggle({{ $event->id }}, '{{ addslashes($event->name) }}', 'start')">
                                                                <i class="fa-solid fa-play me-2"></i> Start Event
                                                            </button>
                                                        @elseif($event->status === 'started')
                                                            <button type="button" class="dropdown-item small text-danger fw-bold" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="confirmAdminToggle({{ $event->id }}, '{{ addslashes($event->name) }}', 'end')">
                                                                <i class="fa-solid fa-stop me-2"></i> End Event
                                                            </button>
                                                        @endif
                                                    </li>
                                                    @if($event->status !== 'completed')
                                                        <li><hr class="dropdown-divider" style="border-top-color: rgba(255,255,255,0.1);"></li>
                                                        <li>
                                                            <button class="dropdown-item small text-white" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'"
                                                                onclick="openAdminEditEvent({{ $event->id }})">
                                                                <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider" style="border-top-color: rgba(255,255,255,0.1);"></li>
                                                        <li>
                                                            <button class="dropdown-item small text-danger" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="confirmAdminDelete({{ $event->id }}, '{{ addslashes($event->name) }}')">
                                                                <i class="fa-solid fa-trash me-2"></i> Delete
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); color: {{ $event->status === 'started' ? '#a8ffcc' : '#fff' }}; font-size: 0.68rem; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">
                                        @if($event->status === 'started')
                                            <i class="fa-solid fa-circle-dot" style="font-size: 0.55rem;"></i> Live
                                        @else
                                            <i class="fa-regular fa-clock" style="font-size: 0.55rem;"></i> Upcoming
                                        @endif
                                    </span>
                                </div>

                                {{-- Body --}}
                                <div class="flex-grow-1 px-4 pt-3 pb-2">
                                    <p class="mb-3 fw-semibold" style="color: rgba(255,255,255,0.4); font-size: 0.78rem; letter-spacing: 0.2px;">{{ Str::limit($event->description, 80) }}</p>

                                    <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                        <div class="d-flex align-items-start gap-3">
                                            <span style="width: 16px; color: #1aad6e; flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                            <span style="color: rgba(255,255,255,0.65); line-height: 1.4;">{{ $event->location ?? 'Location TBD' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span style="width: 16px; color: #1aad6e; flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                            <span style="color: rgba(255,255,255,0.65);">{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'Date TBD' }}@if($event->event_time) &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($event->event_time)->format('h:i A') }}@endif</span>
                                        </div>
                                    </div>

                                    <div style="border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0;"></div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge fw-semibold" style="
                                            font-size: 0.72rem; padding: 5px 12px; border-radius: 20px;
                                            @switch($event->difficulty)
                                                @case('Beginner') background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.3); @break
                                                @case('Improving') background: rgba(13,202,240,0.12); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3); @break
                                                @case('Intermediate') background: rgba(255,193,7,0.12); color: #ffc107; border: 1px solid rgba(255,193,7,0.3); @break
                                                @default background: rgba(108,117,125,0.15); color: #adb5bd; border: 1px solid rgba(108,117,125,0.3);
                                            @endswitch
                                        ">
                                            {{ $event->difficulty }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="text-center py-2" style="background: rgba(26,173,110,0.06); border-top: 1px solid rgba(26,173,110,0.1);">
                                    <small class="fw-bold text-uppercase" style="color: rgba(26,173,110,0.5); font-size: 0.65rem; letter-spacing: 0.8px;">
                                        Created {{ $event->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <!-- Event Management Tab -->
    <div class="tab-pane fade" id="admin-events" role="tabpanel" tabindex="0">

{{-- ═══ Event Management Section ═══ --}}
<div class="card admin-dark-card border-0 rounded-4 overflow-hidden">
    <div class="card-header p-4 pb-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-1" style="color: var(--rc-text);"><i class="fa-solid fa-calendar-check me-2" style="color: #1aad6e;"></i>Event Management</h5>
                <p class="small mb-0" style="color: rgba(255,255,255,0.4);">Search, edit, delete, or end any event in the system.</p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                {{-- Status Filter --}}
                <select class="form-select form-select-sm rounded-pill px-3" id="adminEventStatusFilter" onchange="filterAdminEvents()" style="min-width: 130px; background: var(--rc-surface); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.7);">
                    <option value="all">All Status</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="started">Started</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        {{-- Search Bar --}}
        <div class="mt-3 mb-2">
            <div class="input-group">
                <span class="input-group-text border-0" style="background: var(--rc-surface); color: rgba(255,255,255,0.3);"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" class="form-control border-0" id="adminEventSearchInput" placeholder="Search events by name, location, or organizer..." oninput="filterAdminEvents()" style="background: var(--rc-surface); color: var(--rc-text); border: 1px solid rgba(255,255,255,0.1);">
            </div>
        </div>
    </div>
    <div class="card-body p-4 pt-2">
        @php
            // Event Management should list all events, including admin-created ones.
            $managerEvents = $events;
        @endphp

        @if($managerEvents->isEmpty())
            <div class="text-center py-5">
                <div class="mb-3 opacity-25"><i class="fa-solid fa-calendar-xmark fa-3x" style="color: var(--rc-text-muted);"></i></div>
                <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No events found.</h6>
                <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">There are no events from other organizers in the system yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0" id="adminEventsTable">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Event Name</th>
                            <th>Organizer</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Slots</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($managerEvents as $index => $event)
                            @php
                                $registeredCount = $event->registrations->where('status', 'registered')->count();
                            @endphp
                            <tr class="admin-event-row"
                                data-name="{{ strtolower($event->name) }}"
                                data-location="{{ strtolower($event->location ?? '') }}"
                                data-organizer="{{ strtolower($event->organizer->username ?? '') }}"
                                data-status="{{ $event->status }}">
                                <td class="ps-4 fw-bold" style="color: rgba(255,255,255,0.35);">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold" style="color: var(--rc-text);">{{ $event->name }}</div>
                                    <small style="color: rgba(255,255,255,0.3);">{{ $event->formatted_distance }} &bull; {{ $event->difficulty }}</small>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1" style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.1);">
                                        <i class="fa-solid fa-user me-1"></i>{{ $event->organizer->username ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small" style="color: rgba(255,255,255,0.7);">{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'TBD' }}</div>
                                    <small style="color: rgba(255,255,255,0.3);">{{ $event->event_time ?? '' }}</small>
                                </td>
                                <td><small style="color: rgba(255,255,255,0.4);">{{ Str::limit($event->location, 30) }}</small></td>
                                <td>
                                    <div class="small fw-bold" style="color: rgba(255,255,255,0.7);">{{ $registeredCount }}/{{ $event->slots }}</div>
                                    <div class="progress" style="height: 4px; width: 60px; background: rgba(255,255,255,0.08) !important; border-radius: 10px;">
                                        <div class="progress-bar" style="width: {{ $event->slots > 0 ? min(100, round(($registeredCount / $event->slots) * 100)) : 0 }}%; background: linear-gradient(90deg, #1aad6e, #0dcaf0) !important; border-radius: 10px;"></div>
                                    </div>
                                </td>
                                <td>
                                    @switch($event->status)
                                        @case('upcoming')
                                            <span class="badge bg-info-subtle text-info rounded-pill px-3 py-1"><i class="fa-solid fa-clock me-1"></i>Upcoming</span>
                                            @break
                                        @case('started')
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1"><i class="fa-solid fa-play me-1"></i>Started</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-dark rounded-pill px-3 py-1"><i class="fa-solid fa-flag-checkered me-1"></i>Completed</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">{{ ucfirst($event->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        {{-- View Participants --}}
                                        <button type="button" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(26,173,110,0.12); color: #1aad6e; border: 1px solid rgba(26,173,110,0.25);" onmouseover="this.style.background='rgba(26,173,110,0.25)'" onmouseout="this.style.background='rgba(26,173,110,0.12)'"
                                            onclick="viewAdminParticipants({{ $event->id }})" title="View Participants">
                                            <i class="fa-solid fa-users" style="font-size: 0.7rem;"></i>
                                        </button>
                                        @if($event->status !== 'completed')
                                            {{-- Edit --}}
                                            <button type="button" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(255,193,7,0.12); color: #ffc107; border: 1px solid rgba(255,193,7,0.25);" onmouseover="this.style.background='rgba(255,193,7,0.25)'" onmouseout="this.style.background='rgba(255,193,7,0.12)'"
                                                onclick="openAdminEditEvent({{ $event->id }})" title="Edit">
                                                <i class="fa-solid fa-pen" style="font-size: 0.7rem;"></i>
                                            </button>
                                            {{-- Delete --}}
                                            <button type="button" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(220,53,69,0.12); color: #f87171; border: 1px solid rgba(220,53,69,0.25);" onmouseover="this.style.background='rgba(220,53,69,0.25)'" onmouseout="this.style.background='rgba(220,53,69,0.12)'"
                                                onclick="confirmAdminDelete({{ $event->id }}, '{{ addslashes($event->name) }}')" title="Delete">
                                                <i class="fa-solid fa-trash" style="font-size: 0.7rem;"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small mt-3 text-end" style="color: rgba(255,255,255,0.3);" id="adminEventCount">
                Showing {{ $managerEvents->count() }} event(s)
            </div>
        @endif
    </div>
</div>
    </div>{{-- end #admin-events tab-pane --}}

    <!-- My History Tab (Admin's own completed events) -->
    <div class="tab-pane fade" id="admin-my-history" role="tabpanel" tabindex="0">
        <div class="mt-2 pt-2">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 40px; height: 40px; background: rgba(255,193,7,0.12); border: 1px solid rgba(255,193,7,0.2);">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #ffc107; font-size: 1rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color: var(--rc-text); font-size: 1.05rem;">My Event History</h5>
                    <small style="color: var(--rc-text-muted); font-size: 0.75rem;">Your completed admin-created events</small>
                </div>
                @php
                    $adminCompletedEvents = $events->where('organizer_id', auth()->id())->where('status', 'completed');
                @endphp
                @if($adminCompletedEvents->count() > 0)
                    <span class="badge ms-auto fw-bold" style="background: rgba(255,193,7,0.15); color: #ffc107; border: 1px solid rgba(255,193,7,0.25); border-radius: 20px; padding: 5px 12px; font-size: 0.72rem;">
                        {{ $adminCompletedEvents->count() }} {{ Str::plural('event', $adminCompletedEvents->count()) }}
                    </span>
                @endif
            </div>

            @if($adminCompletedEvents->isEmpty())
                <div class="text-center py-5 rounded-4" style="background: var(--rc-surface-2); border: 1px dashed rgba(255,255,255,0.1);">
                    <div class="mb-3 opacity-25"><i class="fa-solid fa-trophy fa-3x" style="color: var(--rc-text-muted);"></i></div>
                    <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No completed events yet</h6>
                    <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">Your completed events will appear here after they finish.</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach($adminCompletedEvents as $cEvent)
                        @php
                            $cRegisteredCount = $cEvent->registrations->where('status', 'registered')->count();
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="rc-event-card rounded-4 h-100 d-flex flex-column" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s, opacity .18s; opacity: 0.92;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)';this.style.opacity='1'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';this.style.opacity='0.92'">

                                {{-- Muted Gradient Header --}}
                                <div style="background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%); padding: 18px 20px 14px; position: relative;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 70%;">{{ $cEvent->name }}</h5>
                                        <span class="badge fw-bold" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                            {{ $cEvent->formatted_distance }}
                                        </span>
                                    </div>
                                    <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,193,7,0.2); color: #ffd866; font-size: 0.68rem; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,193,7,0.3);">
                                        <i class="fa-solid fa-flag-checkered" style="font-size: 0.6rem;"></i> Finished
                                    </span>
                                    <div class="position-absolute" style="bottom: 8px; right: 14px; opacity: 0.08;">
                                        <i class="fa-solid fa-trophy fa-3x text-white"></i>
                                    </div>
                                </div>

                                {{-- Body --}}
                                <div class="flex-grow-1 px-4 pt-3 pb-2">
                                    <p class="mb-3 fw-semibold" style="color: rgba(255,255,255,0.4); font-size: 0.78rem; letter-spacing: 0.2px;">{{ Str::limit($cEvent->description, 80) }}</p>

                                    <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                        <div class="d-flex align-items-start gap-3">
                                            <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                            <span style="color: rgba(255,255,255,0.55); line-height: 1.4;">{{ $cEvent->location ?? 'Location TBD' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                            <span style="color: rgba(255,255,255,0.55);">{{ $cEvent->event_date ? \Carbon\Carbon::parse($cEvent->event_date)->format('M d, Y') : 'Date TBD' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-solid fa-users"></i></span>
                                            <span style="color: rgba(255,255,255,0.55);">{{ $cRegisteredCount }}/{{ $cEvent->slots }} Participants</span>
                                        </div>
                                    </div>

                                    <div style="border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0;"></div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge fw-semibold" style="
                                            font-size: 0.72rem; padding: 5px 12px; border-radius: 20px;
                                            @switch($cEvent->difficulty)
                                                @case('Beginner') background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.3); @break
                                                @case('Improving') background: rgba(13,202,240,0.12); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3); @break
                                                @case('Intermediate') background: rgba(255,193,7,0.12); color: #ffc107; border: 1px solid rgba(255,193,7,0.3); @break
                                                @default background: rgba(108,117,125,0.15); color: #adb5bd; border: 1px solid rgba(108,117,125,0.3);
                                            @endswitch
                                        ">
                                            {{ $cEvent->difficulty }}
                                        </span>
                                        <span class="fw-bold" style="font-size: 0.72rem; color: rgba(255,193,7,0.7);">
                                            <i class="fa-solid fa-flag-checkered me-1"></i> Completed
                                        </span>
                                    </div>
                                </div>

                                {{-- Action Button --}}
                                <div class="px-4 pb-4 pt-2">
                                    <button class="btn fw-semibold w-100" style="background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'" onclick="viewEventDetails({{ json_encode($cEvent) }})">
                                        <i class="fa-regular fa-eye me-1"></i> View Details
                                    </button>
                                </div>

                                {{-- Footer strip --}}
                                <div class="text-center py-2" style="background: rgba(255,193,7,0.05); border-top: 1px solid rgba(255,193,7,0.1);">
                                    <small class="fw-bold text-uppercase" style="color: rgba(255,193,7,0.4); font-size: 0.65rem; letter-spacing: 0.8px;">
                                        <i class="fa-solid fa-trophy me-1" style="font-size: 0.55rem;"></i>
                                        Event completed {{ $cEvent->event_date ? \Carbon\Carbon::parse($cEvent->event_date)->diffForHumans() : '' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- All History Tab (all completed events system-wide) -->
    <div class="tab-pane fade" id="admin-all-history" role="tabpanel" tabindex="0">
        <div class="mt-2 pt-2">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 40px; height: 40px; background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.2);">
                    <i class="fa-solid fa-folder-open" style="color: #818cf8; font-size: 1rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color: var(--rc-text); font-size: 1.05rem;">All Completed Events</h5>
                    <small style="color: var(--rc-text-muted); font-size: 0.75rem;">System-wide history of all finished events</small>
                </div>
                @php
                    $allCompletedEvents = $events->where('status', 'completed');
                @endphp
                @if($allCompletedEvents->count() > 0)
                    <span class="badge ms-auto fw-bold" style="background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); border-radius: 20px; padding: 5px 12px; font-size: 0.72rem;">
                        {{ $allCompletedEvents->count() }} {{ Str::plural('event', $allCompletedEvents->count()) }}
                    </span>
                @endif
            </div>

            @if($allCompletedEvents->isEmpty())
                <div class="text-center py-5 rounded-4" style="background: var(--rc-surface-2); border: 1px dashed rgba(255,255,255,0.1);">
                    <div class="mb-3 opacity-25"><i class="fa-solid fa-box-archive fa-3x" style="color: var(--rc-text-muted);"></i></div>
                    <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No completed events in the system</h6>
                    <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">Once events are ended, they will appear here as a permanent record.</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach($allCompletedEvents as $hEvent)
                        @php
                            $hRegisteredCount = $hEvent->registrations->where('status', 'registered')->count();
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="rc-event-card rounded-4 h-100 d-flex flex-column" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s, opacity .18s; opacity: 0.92;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)';this.style.opacity='1'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';this.style.opacity='0.92'">

                                {{-- Muted Gradient Header --}}
                                <div style="background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%); padding: 18px 20px 14px; position: relative;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 70%;">{{ $hEvent->name }}</h5>
                                        <span class="badge fw-bold" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                            {{ $hEvent->formatted_distance }}
                                        </span>
                                    </div>
                                    <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,193,7,0.2); color: #ffd866; font-size: 0.68rem; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,193,7,0.3);">
                                        <i class="fa-solid fa-flag-checkered" style="font-size: 0.6rem;"></i> Finished
                                    </span>
                                    <div class="position-absolute" style="bottom: 8px; right: 14px; opacity: 0.08;">
                                        <i class="fa-solid fa-trophy fa-3x text-white"></i>
                                    </div>
                                </div>

                                {{-- Body --}}
                                <div class="flex-grow-1 px-4 pt-3 pb-2">
                                    <p class="mb-3 fw-semibold" style="color: rgba(255,255,255,0.4); font-size: 0.78rem; letter-spacing: 0.2px;">{{ Str::limit($hEvent->description, 80) }}</p>

                                    <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                        <div class="d-flex align-items-start gap-3">
                                            <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                            <span style="color: rgba(255,255,255,0.55); line-height: 1.4;">{{ $hEvent->location ?? 'Location TBD' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                            <span style="color: rgba(255,255,255,0.55);">{{ $hEvent->event_date ? \Carbon\Carbon::parse($hEvent->event_date)->format('M d, Y') : 'Date TBD' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-solid fa-users"></i></span>
                                            <span style="color: rgba(255,255,255,0.55);">{{ $hRegisteredCount }}/{{ $hEvent->slots }} Participants</span>
                                        </div>
                                    </div>

                                    <div style="border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0;"></div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge fw-semibold" style="
                                            font-size: 0.72rem; padding: 5px 12px; border-radius: 20px;
                                            @switch($hEvent->difficulty)
                                                @case('Beginner') background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.3); @break
                                                @case('Improving') background: rgba(13,202,240,0.12); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3); @break
                                                @case('Intermediate') background: rgba(255,193,7,0.12); color: #ffc107; border: 1px solid rgba(255,193,7,0.3); @break
                                                @default background: rgba(108,117,125,0.15); color: #adb5bd; border: 1px solid rgba(108,117,125,0.3);
                                            @endswitch
                                        ">
                                            {{ $hEvent->difficulty }}
                                        </span>
                                        <span class="fw-bold" style="font-size: 0.72rem; color: rgba(255,193,7,0.7);">
                                            <i class="fa-solid fa-flag-checkered me-1"></i> Completed
                                        </span>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="px-4 pb-3 pt-2 d-flex gap-2">
                                    <button class="btn fw-semibold flex-fill" style="background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'" onclick="viewEventDetails({{ json_encode($hEvent) }})">
                                        <i class="fa-regular fa-eye me-1"></i> Details
                                    </button>
                                    <button class="btn fw-semibold" style="background: rgba(26,173,110,0.1); color: #1aad6e; border: 1px solid rgba(26,173,110,0.2); border-radius: 50px; font-size: 0.83rem; padding: 9px 16px;" onmouseover="this.style.background='rgba(26,173,110,0.2)'" onmouseout="this.style.background='rgba(26,173,110,0.1)'" onclick="viewAdminParticipants({{ $hEvent->id }})">
                                        <i class="fa-solid fa-users me-1"></i> {{ $hRegisteredCount }}
                                    </button>
                                </div>

                                {{-- Footer strip --}}
                                <div class="text-center py-2" style="background: rgba(99,102,241,0.05); border-top: 1px solid rgba(99,102,241,0.1);">
                                    <small class="fw-bold text-uppercase" style="color: rgba(99,102,241,0.5); font-size: 0.65rem; letter-spacing: 0.8px;">
                                        <i class="fa-solid fa-user me-1" style="font-size: 0.55rem;"></i>
                                        By {{ $hEvent->organizer->username ?? 'Unknown' }} · {{ $hEvent->event_date ? \Carbon\Carbon::parse($hEvent->event_date)->diffForHumans() : '' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ═══ Edit Event Modal ═══ --}}
<style>
    #adminEditEventModal .modal-footer .btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
</style>
<div class="modal fade" id="adminEditEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen me-2 text-primary"></i>Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adminEditEventForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-muted">Event Name</label>
                            <input type="text" class="form-control bg-light border-0" name="name" id="adminEditName" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Description</label>
                        <textarea class="form-control bg-light border-0" name="description" id="adminEditDescription" rows="3" required></textarea>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Registration Start</label>
                            <input type="date" class="form-control bg-light border-0" name="registration_start" id="adminEditRegStart" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Registration End</label>
                            <input type="date" class="form-control bg-light border-0" name="registration_end" id="adminEditRegEnd" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-uppercase text-muted">Location</label>
                            <input type="text" class="form-control bg-light border-0" name="location" id="adminEditLocation">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Reg. Fee (₱)</label>
                            <input type="number" step="0.01" class="form-control bg-light border-0" name="registration_fee" id="adminEditFee">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Event Date</label>
                            <input type="date" class="form-control bg-light border-0" name="event_date" id="adminEditDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Event Time</label>
                            <input type="time" class="form-control bg-light border-0" name="event_time" id="adminEditTime">
                        </div>
                    </div>
                    {{-- Route Map Pinning --}}
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Route (Pin Start & End)</label>
                        <div class="card bg-transparent overflow-hidden" style="border: 1px solid rgba(255,255,255,0.07); border-radius: 12px;">
                            <div class="d-flex justify-content-between align-items-center p-3" style="background: var(--rc-surface-2);">
                                <small class="fw-bold text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.75rem; letter-spacing: 0.5px;"><i class="fa-solid fa-route me-2" style="color: rgba(255,255,255,0.3);"></i> Pin route on map</small>
                                <button type="button" class="btn btn-sm rounded-pill px-3" style="background: rgba(26,173,110,0.12); color: #1aad6e; border: 1px solid rgba(26,173,110,0.25); font-weight: 600;" onmouseover="this.style.background='rgba(26,173,110,0.25)'" onmouseout="this.style.background='rgba(26,173,110,0.12)'" onclick="toggleMap('admin_edit_map_container', 'admin_edit_map')">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> Open Map
                                </button>
                            </div>
                            <div id="admin_edit_map_container" class="d-none p-3" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                <div class="input-group input-group-sm mb-3 rounded-3 overflow-hidden" style="border: 1px solid rgba(255,255,255,0.1);">
                                    <input type="text" id="admin_edit_map_search" class="form-control" style="background: var(--rc-surface-2); border: none; color: var(--rc-text); padding: 0.5rem 0.75rem; border-radius: 0 !important;" placeholder="Search place (e.g. Barobo)..." onkeypress="if(event.key==='Enter'){event.preventDefault();searchLocation('admin_edit');}">
                                    <button class="btn" style="background: var(--rc-surface-3); border: none; color: var(--rc-text-muted); padding: 0 1rem; border-radius: 0 !important; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="searchLocation('admin_edit')"><i class="fa-solid fa-magnifying-glass"></i></button>
                                    <button class="btn" style="background: var(--rc-surface-3); border: none; color: var(--rc-text-muted); padding: 0 1rem; border-radius: 0 !important; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="locateUser('admin_edit')" title="My Location"><i class="fa-solid fa-crosshairs text-primary"></i></button>
                                    <button class="btn d-none d-lg-inline-block" style="background: var(--rc-surface-3); border: none; color: var(--rc-text-muted); padding: 0 1rem; border-radius: 0 !important; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="toggleFullScreenMap('admin_edit')" title="Full Screen"><i class="fa-solid fa-expand text-info"></i></button>
                                </div>
                                <div id="admin_edit_map" style="height: 300px; width: 100%; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);" class="mb-3"></div>
                                <input type="hidden" name="manual_route_data" id="admin_edit_manual_route_data">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="fw-bold" id="admin_edit_map_status" style="color: rgba(255,255,255,0.4);"><i class="fa-solid fa-circle-info me-1 text-primary"></i> Edit map to re-calculate distance</small>
                                    <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold" onclick="resetMap('admin_edit')">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Reset Pins
                                    </button>
                                </div>
                                <div id="admin_edit_distance_display" class="mt-3 d-none">
                                    <div class="alert py-2 px-3 mb-0 d-flex align-items-center rounded-3 border-0" style="background: rgba(26,173,110,0.1); color: #1aad6e;">
                                        <i class="fa-solid fa-road me-2 text-success"></i>
                                        <span class="fw-bold fs-6">Estimated Distance: <span id="admin_edit_distance_value" class="text-white">--</span> <span class="text-white-50 small">km</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Distance (km)</label>
                            <input type="number" step="0.01" class="form-control bg-light border-0" name="distance" id="admin_edit_distance" readonly placeholder="Auto from map" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Difficulty</label>
                            <select class="form-select bg-light border-0" name="difficulty" id="admin_edit_difficulty" style="pointer-events: none; opacity: 0.7;" tabindex="-1" readonly required>
                                <option value="Beginner">Beginner</option>
                                <option value="Improving">Improving</option>
                                <option value="Intermediate">Intermediate</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Slot Limit</label>
                            <input type="number" min="1" class="form-control bg-light border-0" name="slots" id="adminEditSlots" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex flex-column flex-md-row align-items-stretch gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-fill">
                        <i class="fa-solid fa-save me-1"></i>Save Changes
                    </button>
                    <button type="button" class="btn btn-light rounded-pill px-4 flex-fill" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ Toggle Status Confirm Modal ═══ --}}
<div class="modal fade" id="adminToggleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="adminToggleTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p id="adminToggleMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="adminToggleForm" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="adminToggleBtn">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Delete Confirm Modal ═══ --}}
<div class="modal fade" id="adminDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Delete Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Are you sure you want to delete <strong id="adminDeleteEventName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="adminDeleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Delete User Confirm Modal ═══ --}}
<div class="modal fade" id="adminDeleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-xmark text-danger me-2"></i>Delete Organizer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Are you sure you want to delete the organizer <strong id="adminDeleteUserName"></strong>? This action will permanently delete their account and all their associated events. This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="adminDeleteUserForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-trash me-1"></i>Delete Organizer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══ View Participants Modal ═══ --}}
<div class="modal fade" id="adminParticipantsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-users me-2 text-primary"></i>Participants — <span id="adminParticipantsEventName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="adminParticipantsBody">
                {{-- Dynamic content injected by JS --}}
            </div>
        </div>
    </div>
</div>

{{-- ═══ Create Event Modal ═══ --}}
<style>
    #adminCreateEventModal .modal-dialog {
        max-width: 960px;
        margin: 1rem auto;
    }
    #adminCreateEventModal .modal-content {
        background: var(--rc-surface, #111827);
        border: 1px solid rgba(255,255,255,0.08) !important;
    }
    #adminCreateEventModal .modal-body {
        max-height: min(78vh, 860px);
        overflow-y: auto;
    }
    #adminCreateEventModal .modal-footer {
        display: flex;
        gap: 10px;
    }
    #adminCreateEventModal .modal-footer .btn {
        min-width: 160px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    #adminCreateEventModal #admin_create_map_container .input-group {
        flex-wrap: wrap;
    }
    #adminCreateEventModal #admin_create_map_container .input-group .form-control {
        min-width: 220px;
    }
    @media (max-width: 991.98px) {
        #adminCreateEventModal .modal-dialog {
            max-width: calc(100% - 1rem);
            margin: 0.5rem auto;
        }
        #adminCreateEventModal .modal-body {
            padding: 1rem !important;
            max-height: 80vh;
        }
    }
    @media (max-width: 767.98px) {
        #adminCreateEventModal .modal-header,
        #adminCreateEventModal .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        #adminCreateEventModal .modal-footer {
            flex-direction: column;
            align-items: stretch;
        }
        #adminCreateEventModal .modal-footer .btn {
            width: 100%;
            min-width: 0;
        }
        #adminCreateEventModal #admin_create_map {
            height: 240px !important;
        }
    }
</style>
<div class="modal fade" id="adminCreateEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus me-2 text-primary"></i>Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('events.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-muted">Event Name</label>
                            <input type="text" class="form-control bg-light border-0" name="name" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Description</label>
                        <textarea class="form-control bg-light border-0" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Registration Start</label>
                            <input type="date" class="form-control bg-light border-0" name="registration_start" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Registration End</label>
                            <input type="date" class="form-control bg-light border-0" name="registration_end" required>
                        </div>
                    </div>
                    {{-- Location Selection --}}
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Location Selection</label>
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-6">
                                <div class="hybrid-select">
                                    <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="admin_create_region_search" placeholder="Region..." autocomplete="off" onfocus="showHybridDropdown('admin_create','region')" oninput="filterHybridOptions('admin_create','region')">
                                    <input type="hidden" id="admin_create_region">
                                    <div class="hybrid-dropdown" id="admin_create_region_dropdown"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="hybrid-select">
                                    <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="admin_create_province_search" placeholder="Province..." autocomplete="off" onfocus="showHybridDropdown('admin_create','province')" oninput="filterHybridOptions('admin_create','province')">
                                    <input type="hidden" id="admin_create_province">
                                    <div class="hybrid-dropdown" id="admin_create_province_dropdown"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="hybrid-select">
                                    <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="admin_create_city_search" placeholder="City/Municipality..." autocomplete="off" onfocus="showHybridDropdown('admin_create','city')" oninput="filterHybridOptions('admin_create','city')">
                                    <input type="hidden" id="admin_create_city">
                                    <div class="hybrid-dropdown" id="admin_create_city_dropdown"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="hybrid-select">
                                    <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="admin_create_barangay_search" placeholder="Barangay..." autocomplete="off" onfocus="showHybridDropdown('admin_create','barangay')" oninput="filterHybridOptions('admin_create','barangay')">
                                    <input type="hidden" id="admin_create_barangay">
                                    <div class="hybrid-dropdown" id="admin_create_barangay_dropdown"></div>
                                </div>
                            </div>
                        </div>
                        <label class="form-label small fw-bold text-uppercase text-muted">Street / Purok / Landmark</label>
                        <input type="text" class="form-control bg-light border-0" id="admin_create_street" placeholder="e.g. Purok 1, Rizal St." oninput="updateLocationText('admin_create')">
                        <input type="hidden" name="location" id="admin_create_location" required>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Event Date</label>
                            <input type="date" class="form-control bg-light border-0" name="event_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Event Time</label>
                            <input type="time" class="form-control bg-light border-0" name="event_time" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Reg. Fee (₱)</label>
                            <input type="number" step="0.01" class="form-control bg-light border-0" name="registration_fee" placeholder="0.00" required>
                        </div>
                    </div>
                    {{-- Route Map Pinning --}}
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Route (Pin Start & End)</label>
                        <div class="card bg-transparent overflow-hidden" style="border: 1px solid rgba(255,255,255,0.07); border-radius: 12px;">
                            <div class="d-flex justify-content-between align-items-center p-3" style="background: var(--rc-surface-2);">
                                <small class="fw-bold text-uppercase" style="color: rgba(255,255,255,0.5); font-size: 0.75rem; letter-spacing: 0.5px;"><i class="fa-solid fa-route me-2" style="color: rgba(255,255,255,0.3);"></i> Pin route on map</small>
                                <button type="button" class="btn btn-sm rounded-pill px-3" style="background: rgba(26,173,110,0.12); color: #1aad6e; border: 1px solid rgba(26,173,110,0.25); font-weight: 600;" onmouseover="this.style.background='rgba(26,173,110,0.25)'" onmouseout="this.style.background='rgba(26,173,110,0.12)'" onclick="toggleMap('admin_create_map_container', 'admin_create_map')">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> Open Map
                                </button>
                            </div>
                            <div id="admin_create_map_container" class="d-none p-3" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                <div class="input-group input-group-sm mb-3 rounded-3 overflow-hidden" style="border: 1px solid rgba(255,255,255,0.1);">
                                    <input type="text" id="admin_create_map_search" class="form-control" style="background: var(--rc-surface-2); border: none; color: var(--rc-text); padding: 0.5rem 0.75rem; border-radius: 0 !important;" placeholder="Search place (e.g. Barobo)..." onkeypress="if(event.key==='Enter'){event.preventDefault();searchLocation('admin_create');}">
                                    <button class="btn" style="background: var(--rc-surface-3); border: none; color: var(--rc-text-muted); padding: 0 1rem; border-radius: 0 !important; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="searchLocation('admin_create')"><i class="fa-solid fa-magnifying-glass"></i></button>
                                    <button class="btn" style="background: var(--rc-surface-3); border: none; color: var(--rc-text-muted); padding: 0 1rem; border-radius: 0 !important; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="locateUser('admin_create')" title="My Location"><i class="fa-solid fa-crosshairs text-primary"></i></button>
                                    <button class="btn d-none d-lg-inline-block" style="background: var(--rc-surface-3); border: none; color: var(--rc-text-muted); padding: 0 1rem; border-radius: 0 !important; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="toggleFullScreenMap('admin_create')" title="Full Screen"><i class="fa-solid fa-expand text-info"></i></button>
                                </div>
                                <div id="admin_create_map" style="height: 300px; width: 100%; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);" class="mb-3"></div>
                                <input type="hidden" name="manual_route_data" id="admin_create_manual_route_data">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="fw-bold" id="admin_create_map_status" style="color: rgba(255,255,255,0.4);"><i class="fa-solid fa-circle-info me-1 text-primary"></i> Click map to set Start Point (Green)</small>
                                    <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold" onclick="resetMap('admin_create')">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Reset Pins
                                    </button>
                                </div>
                                <div id="admin_create_distance_display" class="mt-3 d-none">
                                    <div class="alert py-2 px-3 mb-0 d-flex align-items-center rounded-3 border-0" style="background: rgba(26,173,110,0.1); color: #1aad6e;">
                                        <i class="fa-solid fa-road me-2 text-success"></i>
                                        <span class="fw-bold fs-6">Estimated Distance: <span id="admin_create_distance_value" class="text-white">--</span> <span class="text-white-50 small">km</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Distance (km)</label>
                            <input type="number" step="0.01" class="form-control bg-light border-0" name="distance"
                                id="admin_create_distance" readonly placeholder="Auto from map" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Difficulty</label>
                            <select class="form-select bg-light border-0" name="difficulty" id="admin_create_difficulty" style="pointer-events: none; opacity: 0.7;" tabindex="-1" readonly required>
                                <option value="Beginner">Beginner</option>
                                <option value="Improving">Improving</option>
                                <option value="Intermediate">Intermediate</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Slot Limit</label>
                            <input type="number" min="1" class="form-control bg-light border-0" name="slots" placeholder="e.g. 100" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-plus me-1"></i>Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ View Event Details Modal ═══ --}}
<style>
    #viewEventModal .modal-content {
        background: var(--rc-surface, #111827) !important;
        border: 1px solid rgba(255,255,255,0.07) !important;
        border-radius: 1.25rem !important;
    }
    #viewEventModal .ved-info-row {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 13px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.87rem;
    }
    #viewEventModal .ved-info-row:last-child { border-bottom: none; }
    #viewEventModal .ved-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; }
    #viewEventModal .ved-label { font-size: 0.67rem; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 3px; }
    #viewEventModal .ved-value { color: rgba(255,255,255,0.85); font-weight: 600; line-height: 1.4; }
</style>
<div class="modal fade" id="viewEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow-lg overflow-hidden">

            {{-- Gradient Banner --}}
            <div style="background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%); padding: 28px 24px 22px; position: relative;">
                <button type="button" data-bs-dismiss="modal" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.18);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:1.1rem;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
                <span style="display:inline-block;background:rgba(255,255,255,0.18);color:#fff;font-size:0.67rem;font-weight:700;letter-spacing:0.5px;padding:3px 12px;border-radius:20px;margin-bottom:10px;">RUNNING EVENT</span>
                <h3 class="fw-bold text-white mb-2 lh-sm" id="view_event_name" style="font-size:1.3rem;"></h3>
                <div class="d-flex gap-2 flex-wrap mt-1">
                    <span id="view_event_difficulty" style="background:rgba(255,255,255,0.15);color:#a8ffcc;font-size:0.72rem;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,0.2);"></span>
                    <span id="view_event_distance" style="background:rgba(255,255,255,0.15);color:#fff;font-size:0.72rem;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,0.2);"></span>
                </div>
            </div>

            {{-- Scrollable Body --}}
            <div style="padding:20px 24px 8px;max-height:58vh;overflow-y:auto;">
                <p id="view_event_description" style="color:rgba(255,255,255,0.5);font-size:0.85rem;margin-bottom:18px;white-space:pre-line;line-height:1.6;"></p>

                <div class="ved-info-row">
                    <div class="ved-icon" style="background:rgba(26,173,110,0.15);"><i class="fa-solid fa-location-dot" style="color:#1aad6e;"></i></div>
                    <div><div class="ved-label">Location</div><div class="ved-value" id="view_event_location"></div></div>
                </div>
                <div class="ved-info-row">
                    <div class="ved-icon" style="background:rgba(13,202,240,0.12);"><i class="fa-regular fa-calendar" style="color:#0dcaf0;"></i></div>
                    <div><div class="ved-label">Event Date &amp; Time</div><div class="ved-value" id="view_event_datetime"></div></div>
                </div>
                <div class="ved-info-row">
                    <div class="ved-icon" style="background:rgba(255,193,7,0.12);"><i class="fa-solid fa-tag" style="color:#ffc107;"></i></div>
                    <div><div class="ved-label">Registration Fee</div><div class="ved-value" id="view_event_fee"></div></div>
                </div>
                <div class="ved-info-row">
                    <div class="ved-icon" style="background:rgba(99,102,241,0.15);"><i class="fa-solid fa-users" style="color:#818cf8;"></i></div>
                    <div><div class="ved-label">Available Slots</div><div class="ved-value" id="view_event_slots"></div></div>
                </div>

                {{-- Registration period --}}
                <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:14px 20px;margin:16px 0;display:flex;">
                    <div style="flex:1;text-align:center;border-right:1px solid rgba(255,255,255,0.07);padding-right:16px;">
                        <div class="ved-label" style="margin-bottom:5px;">Opens</div>
                        <div class="fw-bold" style="color:#1aad6e;font-size:0.88rem;" id="view_event_reg_start"></div>
                    </div>
                    <div style="flex:1;text-align:center;padding-left:16px;">
                        <div class="ved-label" style="margin-bottom:5px;">Closes</div>
                        <div class="fw-bold" style="color:#f87171;font-size:0.88rem;" id="view_event_reg_end"></div>
                    </div>
                </div>

                {{-- Organizer --}}
                <div class="d-flex align-items-center gap-3 mt-2 pb-4">
                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#0e6e3e,#1aad6e);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid fa-user text-white" style="font-size:0.9rem;"></i>
                    </div>
                    <div>
                        <div class="ved-label" style="margin-bottom:2px;">Organized by</div>
                        <div class="ved-value fw-bold" id="view_event_organizer"></div>
                    </div>
                </div>
            </div>

            {{-- Sticky Footer --}}
            <div style="padding:14px 24px;border-top:1px solid rgba(255,255,255,0.06);">
                <button type="button" class="btn w-100 fw-semibold" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1);border-radius:50px;padding:12px;font-size:0.9rem;" onmouseover="this.style.background='rgba(255,255,255,0.13)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ═══ Live Monitor Modal ═══ --}}
<div class="modal fade" id="liveMonitorModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="liveMonitorTitle">Event Live Monitor</h5>
                    <small class="opacity-75">Tracking Active Runners (Updates every 5s)</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-row overflow-hidden" style="height: 100%;">
                <!-- Runner Ranking -->
                <div class="bg-white border-end d-flex flex-column shadow-sm" style="width: 25%; min-width: 280px; z-index: 1050;">
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-list-ol text-primary me-2"></i>Live Leaderboard</h6>
                            <span class="badge bg-success rounded-pill"><span id="lmRunnerCount">0</span> Active</span>
                    </div>
                    <div class="flex-grow-1 overflow-auto bg-white" id="runnerRankingList">
                        <div class="text-center p-5 text-muted small">
                            <span class="spinner-grow spinner-grow-sm text-primary mb-2" role="status"></span><br>
                            Acquiring runner data...
                        </div>
                    </div>
                </div>
                
                <!-- Leaflet container -->
                <div class="position-relative flex-grow-1" style="height: 100%;">
                    <div id="liveMonitorMap" style="width: 100%; height: 100%; background: #e5e5e5;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ═══ Admin Event Data ═══
    const adminParticipantsData = @json($participantsJson);
    const adminEventsData = @json($events);

    // ═══ View Event Details Logic ═══
    function viewEventDetails(event) {
        document.getElementById('view_event_name').textContent = event.name || '';
        document.getElementById('view_event_description').textContent = event.description || 'No description provided.';
        document.getElementById('view_event_location').textContent = event.location || 'Location TBD';
        document.getElementById('view_event_difficulty').textContent = event.difficulty || '--';
        document.getElementById('view_event_distance').textContent = (event.distance || '--') + ' km';
        document.getElementById('view_event_slots').textContent = (event.slots || '--') + ' slots';

        // Fee
        const fee = parseFloat(event.registration_fee);
        document.getElementById('view_event_fee').textContent = fee > 0 ? '₱' + fee.toFixed(2) : 'Free';

        // Date & Time
        let dtText = 'TBD';
        if (event.event_date) {
            const d = new Date(event.event_date + 'T00:00:00');
            dtText = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            if (event.event_time) {
                const [h, m] = event.event_time.split(':');
                const ampm = h >= 12 ? 'PM' : 'AM';
                dtText += ' at ' + ((h % 12) || 12) + ':' + m + ' ' + ampm;
            }
        }
        document.getElementById('view_event_datetime').textContent = dtText;

        // Registration period
        const formatDate = (str) => {
            if (!str) return '--';
            const d = new Date(str + 'T00:00:00');
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        };
        document.getElementById('view_event_reg_start').textContent = formatDate(event.registration_start);
        document.getElementById('view_event_reg_end').textContent = formatDate(event.registration_end);

        // Organizer
        const orgName = event.organizer ? (event.organizer.username || 'Unknown') : 'Unknown';
        document.getElementById('view_event_organizer').textContent = orgName;

        showAdminModal('viewEventModal');
    }

    // ═══ Persist Tab State ═══
    document.addEventListener('DOMContentLoaded', () => {
        const storedTab = sessionStorage.getItem('adminDashboardTab');
        if (storedTab) {
            const triggerEl = document.querySelector(`button[data-bs-target="${storedTab}"]`);
            if (triggerEl) {
                bootstrap.Tab.getOrCreateInstance(triggerEl).show();
            }
        }
        
        const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(el => {
            el.addEventListener('shown.bs.tab', event => {
                const targetId = event.target.getAttribute('data-bs-target');
                sessionStorage.setItem('adminDashboardTab', targetId);
            });
        });
    });

    // ═══ Live Monitor Logic ═══
    let liveMap = null;
    let routePolyline = null;
    let runnerMarkers = {}; // stores active markers by user_id
    let runnerLiveRoutes = {}; // stores active trailed polylines
    let pollingInterval = null;

    function openLiveMonitor(eventId, eventName) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        document.getElementById('liveMonitorTitle').textContent = eventName + ' - Live Monitor';
        showAdminModal('liveMonitorModal');

        // Wait until modal is fully shown to initialize the map, otherwise Leaflet sizing breaks
        document.getElementById('liveMonitorModal').addEventListener('shown.bs.modal', function onModalShow() {
            document.getElementById('liveMonitorModal').removeEventListener('shown.bs.modal', onModalShow);
            
            initOrResetMap();
            pollRunnerLocations(eventId);

            // Start polling every 5 seconds
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(() => pollRunnerLocations(eventId), 5000);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('liveMonitorModal')?.addEventListener('hidden.bs.modal', function () {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        });
    });

    function initOrResetMap() {
        if (!liveMap) {
            liveMap = L.map('liveMonitorMap').setView([14.5995, 120.9842], 13); // Default to Manila
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(liveMap);
        }
        
        // Clear existing runners
        for (let id in runnerMarkers) {
            liveMap.removeLayer(runnerMarkers[id]);
        }
        runnerMarkers = {};

        // Clear live routes
        for (let id in runnerLiveRoutes) {
            liveMap.removeLayer(runnerLiveRoutes[id]);
        }
        runnerLiveRoutes = {};

        if (routePolyline) {
            if (routePolyline !== 'loading') {
                liveMap.removeLayer(routePolyline);
            }
            routePolyline = null;
        }
    }

    async function pollRunnerLocations(eventId) {
        try {
            const response = await fetch(`/tracking/event/${eventId}/locations`);
            const data = await response.json();

            if (data.status === 'success') {
                // Update Runner Leaderboard UI
                const runnerCountEl = document.getElementById('lmRunnerCount');
                if(runnerCountEl) runnerCountEl.textContent = data.runners.length;
                
                const leaderboardHtml = data.runners.length === 0 
                    ? `<div class="text-center p-5 text-muted small"><i class="fa-solid fa-ghost fa-2x mb-2 opacity-50"></i><br>No runners active</div>`
                    : data.runners.map((runner, index) => `
                        <div class="p-3 border-bottom hover-bg-light transition-all">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="fw-bold fs-5 text-dark me-3" style="min-width: 20px;">#${index + 1}</div>
                                    <div>
                                        <div class="fw-bold text-dark">${runner.name} ${runner.has_emergency ? '<span class="badge bg-danger ms-1" style="font-size: 0.6rem;">SOS</span>' : ''}</div>
                                        <div class="small text-muted"><i class="fa-solid fa-route me-1"></i>${parseFloat(runner.distance || 0).toFixed(2)} km</div>
                                    </div>
                                </div>
                                <div class="small text-muted" style="font-size: 0.7rem;">${runner.last_tracked_at}</div>
                            </div>
                        </div>
                    `).join('');

                const rankingList = document.getElementById('runnerRankingList');
                if(rankingList) rankingList.innerHTML = leaderboardHtml;

                // Draw Route from GeoJSON if it exists and hasn't been drawn yet.
                // Prefer API-provided data to avoid /storage/* permission issues.
                if (!routePolyline && (data.route_geojson || data.route_data)) {
                    routePolyline = 'loading'; // Temporary marker to prevent duplication

                    const drawRoute = (geojsonData) => {
                        routePolyline = L.geoJSON(geojsonData, {
                            style: function () {
                                return {color: "#3b82f6", weight: 4, opacity: 0.6};
                            },
                            pointToLayer: function (feature, latlng) {
                                const markerType = feature?.properties?.type;
                                let color = markerType === 'start' ? '#22c55e' : '#ef4444';
                                const icon = L.divIcon({
                                    className: 'custom-div-icon',
                                    html: `<div style='background-color:${color}; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 6px rgba(0,0,0,0.6);'></div>`,
                                    iconSize: [14, 14], iconAnchor: [7, 7]
                                });
                                return L.marker(latlng, {icon: icon});
                            }
                        }).addTo(liveMap);
                        liveMap.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
                    };

                    try {
                        if (data.route_geojson && typeof data.route_geojson === 'object') {
                            drawRoute(data.route_geojson);
                        } else {
                            fetch('/storage/' + data.route_data)
                                .then(res => {
                                    if (!res.ok) {
                                        throw new Error(`HTTP ${res.status}`);
                                    }
                                    return res.json();
                                })
                                .then(drawRoute)
                                .catch(err => {
                                    console.error('Error loading GeoJSON route', err);
                                    routePolyline = null;
                                });
                        }
                    } catch (err) {
                        console.error('Error parsing GeoJSON route', err);
                        routePolyline = null;
                    }
                }

                // Prepare an array of active user IDs from this fetch
                const activeIds = data.runners.map(r => r.id);

                // Remove markers that are no longer broadcating or active
                for (let id in runnerMarkers) {
                    if (!activeIds.includes(parseInt(id))) {
                        liveMap.removeLayer(runnerMarkers[id]);
                        delete runnerMarkers[id];
                        if (runnerLiveRoutes[id]) {
                            liveMap.removeLayer(runnerLiveRoutes[id]);
                            delete runnerLiveRoutes[id];
                        }
                    }
                }

                // Create or Update Markers (with rank number)
                data.runners.forEach((runner, index) => {
                    const rank = index + 1;
                    const latLng = [runner.lat, runner.lng];

                    // Custom Marker inside divIcon
                    const htmlContent = `
                        <div style="background-color: ${runner.has_emergency ? '#ef4444' : '#3b82f6'}; color: white; width: 24px; height: 24px; border-radius: 50%; border: 3px solid ${runner.has_emergency ? '#fee2e2' : 'white'}; box-shadow: 0 0 ${runner.has_emergency ? '16px rgba(239,68,68,0.8)' : '10px rgba(0,0,0,0.5)'}; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 10px; ${runner.has_emergency ? 'animation: pulse 1s infinite;' : ''}">
                            ${rank}
                        </div>
                    `;

                    const runnerIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: htmlContent,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12],
                        popupAnchor: [0, -12]
                    });

                    const popupContent = `<b>#${rank} - ${runner.name}</b><br><small class="text-muted">Distance: ${parseFloat(runner.distance || 0).toFixed(2)} km<br>Last updated: ${runner.last_tracked_at}</small>${runner.has_emergency ? '<br><button class="btn btn-sm btn-danger mt-2 w-100 fw-bold shadow-sm" style="font-size: 0.75rem" onclick="resolveEmergency(' + eventId + ', '+runner.id+')">Resolve SOS</button>' : ''}`;

                    if (runnerMarkers[runner.id]) {
                        // Update existing marker smoothly
                        runnerMarkers[runner.id].setLatLng(latLng);
                        runnerMarkers[runner.id].setIcon(runnerIcon);
                        runnerMarkers[runner.id].getPopup().setContent(popupContent);
                    } else {
                        // Create new marker
                        const marker = L.marker(latLng, { icon: runnerIcon })
                            .addTo(liveMap)
                            .bindPopup(popupContent);
                        
                        runnerMarkers[runner.id] = marker;
                    }

                    // Draw live tracking path
                    if (runner.live_route_data && runner.live_route_data.length > 0) {
                        const trailPts = runner.live_route_data.map(pt => [pt.lat, pt.lon || pt.lng]);
                        if (runnerLiveRoutes[runner.id]) {
                            runnerLiveRoutes[runner.id].setLatLngs(trailPts);
                        } else {
                            runnerLiveRoutes[runner.id] = L.polyline(trailPts, { 
                                color: '#22c55e', 
                                weight: 4, 
                                opacity: 0.8,
                                dashArray: '5, 8'
                            }).addTo(liveMap);
                        }
                    }
                });

                // If we have runners but no route polyline bounds to fit to, let's bound to the runners instead
                if (!routePolyline && data.runners.length > 0) {
                    const group = new L.featureGroup(Object.values(runnerMarkers));
                    liveMap.fitBounds(group.getBounds().pad(0.1));
                }
            }
        } catch (error) {
            console.error("Polling error", error);
        }
    }

    // ═══ Helper: Safely show a modal (avoids aria-hidden focus conflict) ═══
    function showAdminModal(modalId) {
        const el = document.getElementById(modalId);
        const instance = bootstrap.Modal.getOrCreateInstance(el);
        instance.show();
    }

    async function resolveEmergency(eventId, userId) {
        if(!confirm("Are you sure you want to resolve this emergency SOS?")) return;
        try {
            const response = await fetch('/tracking/emergency/' + eventId + '/resolve/' + userId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                }
            });
            const data = await response.json();
            if(data.status === 'success') {
                alert('Emergency resolved');
                pollRunnerLocations(eventId);
            }
        } catch(e) {
            console.error(e);
        }
    }

    // Blur focused element inside any modal before it hides to prevent aria-hidden warning
    document.addEventListener('hide.bs.modal', function(e) {
        if (document.activeElement && e.target.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    });

    // ═══ Search & Filter ═══
    function filterAdminEvents() {
        const searchTerm = document.getElementById('adminEventSearchInput').value.toLowerCase().trim();
        const statusFilter = document.getElementById('adminEventStatusFilter').value;
        const rows = document.querySelectorAll('.admin-event-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.dataset.name;
            const location = row.dataset.location;
            const organizer = row.dataset.organizer;
            const status = row.dataset.status;

            const matchesSearch = !searchTerm || name.includes(searchTerm) || location.includes(searchTerm) || organizer.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || status === statusFilter;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('adminEventCount').textContent = `Showing ${visibleCount} event(s)`;
    }

    // ═══ Edit Event ═══
    function openAdminEditEvent(eventId) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        if (maps['admin_edit']) resetMap('admin_edit');
        const event = adminEventsData.find(e => e.id === eventId);
        if (!event) return;

        document.getElementById('adminEditEventForm').action = `/events/${event.id}`;
        document.getElementById('adminEditName').value = event.name;
        document.getElementById('admin_edit_distance').value = event.distance;
        document.getElementById('adminEditDescription').value = event.description;
        document.getElementById('admin_edit_difficulty').value = event.difficulty;
        document.getElementById('adminEditRegStart').value = event.registration_start ? event.registration_start.split('T')[0] : '';
        document.getElementById('adminEditRegEnd').value = event.registration_end ? event.registration_end.split('T')[0] : '';
        document.getElementById('adminEditDate').value = event.event_date ? event.event_date.split('T')[0] : '';
        document.getElementById('adminEditTime').value = event.event_time || '';
        document.getElementById('adminEditSlots').value = event.slots;
        document.getElementById('adminEditLocation').value = event.location || '';
        document.getElementById('adminEditFee').value = event.registration_fee;

        showAdminModal('adminEditEventModal');
    }

    // ═══ Toggle Status (Start / End) ═══
    function confirmAdminToggle(eventId, eventName, action) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        const title = 'Confirm Action';
        const message = action === 'start'
            ? `Are you sure you want to <strong>start</strong> the event "<strong>${eventName}</strong>"? Runners will be able to join live tracking.`
            : `Are you sure you want to <strong>end</strong> the event "<strong>${eventName}</strong>"? All active runners' tracking will be stopped.`;
        const btnClass = action === 'start' ? 'btn-success' : 'btn-warning';

        document.getElementById('adminToggleTitle').textContent = title;
        document.getElementById('adminToggleMessage').innerHTML = message;
        document.getElementById('adminToggleForm').action = `/events/${eventId}/toggle-status`;

        const btn = document.getElementById('adminToggleBtn');
        btn.className = `btn ${btnClass} rounded-pill px-4 fw-bold`;
        btn.innerHTML = action === 'start'
            ? '<i class="fa-solid fa-play me-1"></i>Start Event'
            : '<i class="fa-solid fa-stop me-1"></i>End Event';

        showAdminModal('adminToggleModal');
    }

    // ═══ Delete Event ═══
    function confirmAdminDelete(eventId, eventName) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        document.getElementById('adminDeleteEventName').textContent = eventName;
        document.getElementById('adminDeleteForm').action = `/events/${eventId}`;
        showAdminModal('adminDeleteModal');
    }

    // ═══ Delete User ═══
    function confirmAdminDeleteUser(userId, username) {
        document.getElementById('adminDeleteUserName').textContent = username;
        document.getElementById('adminDeleteUserForm').action = `/dashboard/users/${userId}`;
        showAdminModal('adminDeleteUserModal');
    }

    // ═══ View Participants ═══
    function viewAdminParticipants(eventId) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        const eventData = adminParticipantsData.find(e => e.id === eventId);
        if (!eventData) return;

        document.getElementById('adminParticipantsEventName').textContent = eventData.name;

        const body = document.getElementById('adminParticipantsBody');

        if (eventData.registrations.length === 0) {
            body.innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-50"><i class="fa-solid fa-users-slash fa-3x"></i></div>
                    <h6 class="fw-bold text-muted">No participants yet.</h6>
                </div>
            `;
        } else {
            const statusColor = eventData.status === 'started' ? 'success' : eventData.status === 'completed' ? 'dark' : 'info';
            body.innerHTML = `
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                        <i class="fa-solid fa-users me-1"></i>${eventData.registrations.length} Participant(s)
                    </span>
                    <span class="badge bg-${statusColor}-subtle text-${statusColor} rounded-pill px-3 py-2">
                        ${eventData.status.charAt(0).toUpperCase() + eventData.status.slice(1)}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase border-bottom">
                                <th class="fw-bold py-2">Bib#</th>
                                <th class="fw-bold py-2">Name</th>
                                <th class="fw-bold py-2">Email</th>
                                <th class="fw-bold py-2">Registered</th>
                                <th class="fw-bold py-2">Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${eventData.registrations.map(r => `
                                <tr>
                                    <td><span class="badge bg-light text-dark fw-bold">${r.bib_number}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px; height:32px; font-size: 0.8rem;">
                                                ${r.initial}
                                            </div>
                                            <span class="fw-bold small">${r.name}</span>
                                        </div>
                                    </td>
                                    <td><small class="text-muted">${r.email}</small></td>
                                    <td><small class="text-muted">${r.date}</small></td>
                                    <td>
                                        <span class="badge ${r.payment_status === 'paid' ? 'bg-success-subtle text-success' : r.payment_status === 'free' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning'} rounded-pill px-2 small">
                                            ${r.payment_status || 'pending'}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        showAdminModal('adminParticipantsModal');
    }
</script>
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    // ═══ Location & Map JS for Admin Create Event ═══
    const locData = { regions: [], provinces: [], cities: [], barangays: null };
    let searchTimeout = {};
    let maps = {};
    let pins = {
        admin_create: { waypoints: [], routeLayer: null },
        admin_edit: { waypoints: [], routeLayer: null }
    };

    // Load location datasets
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const [regions, provinces, cities] = await Promise.all([
                fetch('/data/regions.json').then(r => r.json()),
                fetch('/data/provinces.json').then(r => r.json()),
                fetch('/data/cities.json').then(r => r.json())
            ]);
            locData.regions = regions;
            locData.provinces = provinces;
            locData.cities = cities;
        } catch (e) {
            console.error('Failed to load location data:', e);
        }
    });

    async function ensureBarangaysLoaded() {
        if (!locData.barangays) {
            locData.barangays = await fetch('/data/barangays.json').then(r => r.json());
        }
    }

    const levelConfig = {
        region:   { code: 'regCode',     label: 'regDesc' },
        province: { code: 'provCode',    label: 'provDesc' },
        city:     { code: 'citymunCode', label: 'citymunDesc' },
        barangay: { code: 'brgyCode',    label: 'brgyDesc' }
    };
    const levelOrder = ['region', 'province', 'city', 'barangay'];

    function getAvailableItems(type, level) {
        const regVal  = document.getElementById(type + '_region').value;
        const provVal = document.getElementById(type + '_province').value;
        const cityVal = document.getElementById(type + '_city').value;
        switch (level) {
            case 'region':   return locData.regions;
            case 'province': return regVal  ? locData.provinces.filter(p => p.regCode === regVal) : locData.provinces;
            case 'city':     return provVal ? locData.cities.filter(c => c.provCode === provVal)  : [];
            case 'barangay': return cityVal && locData.barangays ? locData.barangays.filter(b => b.citymunCode === cityVal) : [];
            default: return [];
        }
    }

    function renderDropdown(type, level, items, query) {
        const dd = document.getElementById(type + '_' + level + '_dropdown');
        const cfg = levelConfig[level];
        if (!items.length) {
            dd.innerHTML = '<div class="hd-empty">No results</div>';
            return;
        }
        const q = (query || '').toLowerCase();
        dd.innerHTML = items.slice(0, 100).map(item => {
            let text = item[cfg.label];
            if (q && text.toLowerCase().includes(q)) {
                const idx = text.toLowerCase().indexOf(q);
                text = text.substring(0, idx) + '<span class="hd-match">' + text.substring(idx, idx + q.length) + '</span>' + text.substring(idx + q.length);
            }
            return `<div class="hd-item" onclick="selectHybridOption('${type}','${level}','${item[cfg.code]}','${item[cfg.label].replace(/'/g, "\\\\'")}')">${text}</div>`;
        }).join('');
    }

    async function showHybridDropdown(type, level) {
        if (level === 'barangay') await ensureBarangaysLoaded();
        const items = getAvailableItems(type, level);
        const dd = document.getElementById(type + '_' + level + '_dropdown');
        renderDropdown(type, level, items, '');
        dd.classList.add('show');
    }

    async function filterHybridOptions(type, level) {
        if (level === 'barangay') await ensureBarangaysLoaded();
        const search = document.getElementById(type + '_' + level + '_search');
        const query = search.value.trim().toLowerCase();
        const dd = document.getElementById(type + '_' + level + '_dropdown');
        let items = getAvailableItems(type, level);
        if (query) {
            items = items.filter(item => item[levelConfig[level].label].toLowerCase().includes(query));
        }
        renderDropdown(type, level, items, query);
        dd.classList.add('show');
        document.getElementById(type + '_' + level).value = '';
    }

    function selectHybridOption(type, level, code, label) {
        document.getElementById(type + '_' + level).value = code;
        document.getElementById(type + '_' + level + '_search').value = label;
        document.getElementById(type + '_' + level + '_dropdown').classList.remove('show');
        const idx = levelOrder.indexOf(level);
        for (let i = idx + 1; i < levelOrder.length; i++) {
            document.getElementById(type + '_' + levelOrder[i]).value = '';
            document.getElementById(type + '_' + levelOrder[i] + '_search').value = '';
        }
        updateLocationText(type);
    }

    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('hybrid-input')) {
            document.querySelectorAll('.hybrid-dropdown.show').forEach(dd => dd.classList.remove('show'));
        }
    });

    function updateLocationText(type) {
        const street   = document.getElementById(type + '_street');
        const brgySearch = document.getElementById(type + '_barangay_search');
        const citySearch = document.getElementById(type + '_city_search');
        const provSearch = document.getElementById(type + '_province_search');
        let parts = [];
        if (street && street.value.trim()) parts.push(street.value.trim());
        if (brgySearch && brgySearch.value.trim() && document.getElementById(type + '_barangay').value) parts.push('Brgy. ' + brgySearch.value.trim());
        if (citySearch && citySearch.value.trim() && document.getElementById(type + '_city').value) parts.push(citySearch.value.trim());
        if (provSearch && provSearch.value.trim() && document.getElementById(type + '_province').value) parts.push(provSearch.value.trim());
        const fullAddress = parts.join(', ');
        const locInput = document.getElementById(type + '_location');
        if (locInput) locInput.value = fullAddress;
        
        // Removed auto-search feature so map panning/zooming is isolated from address fields
    }

    // ═══ Auto-select Difficulty based on Distance ═══
    function autoSelectDifficulty(type, distanceKm) {
        const km = parseFloat(distanceKm);
        let difficulty = 'Beginner';
        if (km > 10.0) {
            difficulty = 'Intermediate';
        } else if (km > 5.0) {
            difficulty = 'Improving';
        }
        const selectId = type + '_difficulty';
        const select = document.getElementById(selectId);
        if (select) {
            select.value = difficulty;
        }
    }

    // ═══ Map Functions ═══
    function toggleMap(containerId, mapId) {
        const container = document.getElementById(containerId);
        container.classList.toggle('d-none');
        if (!container.classList.contains('d-none')) {
            const type = mapId.replace('_map', '');
            if (!maps[type]) {
                initMap(type, mapId);
            } else {
                setTimeout(() => { maps[type].invalidateSize(); }, 200);
            }
        }
    }

    function initMap(type, mapId) {
        maps[type] = L.map(mapId).setView([8.5, 126.2], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(maps[type]);
        maps[type].on('click', function(e) {
            handleMapClick(type, e.latlng);
        });
    }

    function handleMapClick(type, latlng) {
        if (!pins[type]) pins[type] = { waypoints: [], routeLayer: null };
        const statusEl = document.getElementById(type + '_map_status');
        
        let pointColor = pins[type].waypoints.length === 0 ? '#22c55e' : '#ef4444';
        let pointLabel = pins[type].waypoints.length === 0 ? 'Start Point' : 'Waypoint ' + pins[type].waypoints.length;
        
        const icon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div style='background-color:${pointColor}; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 6px rgba(0,0,0,0.6);'></div>`,
            iconSize: [14, 14], iconAnchor: [7, 7]
        });
        
        let marker = L.marker(latlng, {icon: icon}).addTo(maps[type]).bindPopup(pointLabel).openPopup();
        pins[type].waypoints.push(marker);

        if (pins[type].waypoints.length === 1) {
            statusEl.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> Now click to add points to your route';
        } else {
            statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Calculating road route...';
            if (pins[type].routeLayer) maps[type].removeLayer(pins[type].routeLayer);
            fetchRoadRoute(type);
        }
    }

    function fetchRoadRoute(type) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        if (pins[type].waypoints.length < 2) return;
        
        const coordsStr = pins[type].waypoints.map(m => {
            const ll = m.getLatLng();
            return `${ll.lng},${ll.lat}`;
        }).join(';');
        
        const statusEl = document.getElementById(type + '_map_status');
        const url = `https://router.project-osrm.org/route/v1/driving/${coordsStr}?overview=full&geometries=geojson`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.code !== 'Ok' || !data.routes.length) {
                    statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> No road route found. Try different points.';
                    return;
                }
                const route = data.routes[0];
                const distanceKm = Math.round(route.distance / 1000);
                const routeCoords = route.geometry.coordinates.map(c => [c[1], c[0]]);
                pins[type].routeLayer = L.polyline(routeCoords, {
                    color: '#3b82f6', weight: 4, opacity: 0.8
                }).addTo(maps[type]);
                maps[type].fitBounds(pins[type].routeLayer.getBounds().pad(0.15));
                const distDisplay = document.getElementById(type + '_distance_display');
                const distValue = document.getElementById(type + '_distance_value');
                if (distDisplay) distDisplay.classList.remove('d-none');
                if (distValue) distValue.textContent = distanceKm;
                const distInput = document.getElementById(type + '_distance');
                if (distInput) distInput.value = distanceKm;
                // Auto-select difficulty based on distance
                autoSelectDifficulty(type, distanceKm);
                statusEl.innerHTML = '<i class="fa-solid fa-check-circle me-1 text-success"></i> Road route calculated! ' + distanceKm + ' km';
                generateRoadGeoJSON(type, route.geometry, distanceKm);
            })
            .catch(err => {
                console.error('OSRM error:', err);
                statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Route calculation failed. Check internet.';
            });
    }

    function generateRoadGeoJSON(type, roadGeometry, distanceKm) {
        const features = [];
        pins[type].waypoints.forEach((m, idx) => {
            const ll = m.getLatLng();
            const isStart = idx === 0;
            const isEnd = idx === pins[type].waypoints.length - 1;
            let pointType = isStart ? 'start' : (isEnd ? 'end' : 'waypoint');
            let name = isStart ? 'Start Point' : (isEnd ? 'End Point' : 'Waypoint ' + idx);
            
            features.push({
                "type": "Feature",
                "properties": { "name": name, "type": pointType },
                "geometry": { "type": "Point", "coordinates": [ll.lng, ll.lat] }
            });
        });

        features.push({
            "type": "Feature",
            "properties": { "name": "Road Route", "type": "route", "distance_km": parseFloat(distanceKm) },
            "geometry": roadGeometry
        });

        const geojson = {
            "type": "FeatureCollection",
            "properties": { "distance_km": parseFloat(distanceKm) },
            "features": features
        };
        document.getElementById(type + '_manual_route_data').value = JSON.stringify(geojson);
    }

    function resetMap(type) {
        if (!pins[type]) return;
        if (pins[type].waypoints) {
            pins[type].waypoints.forEach(w => maps[type].removeLayer(w));
        }
        if (pins[type].routeLayer) maps[type].removeLayer(pins[type].routeLayer);
        pins[type] = { waypoints: [], routeLayer: null };
        document.getElementById(type + '_manual_route_data').value = '';
        document.getElementById(type + '_map_status').innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> Click map to set Start Point (Green)';
        const distDisplay = document.getElementById(type + '_distance_display');
        if (distDisplay) distDisplay.classList.add('d-none');
        const distInput = document.getElementById(type + '_distance');
        if (distInput) distInput.value = '';
        // Reset difficulty back to default
        const diffSelect = document.getElementById(type + '_difficulty');
        if (diffSelect) diffSelect.value = 'Beginner';
    }

    function searchLocation(type, overrides) {
        if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
        const query = overrides || document.getElementById(type + '_map_search').value;
        if (!query || !maps[type]) return;
        const btn = document.querySelector(`#${type}_map_container .btn-primary`);
        const statusEl = document.getElementById(type + '_map_status');
        const originalContent = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Searching...';
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (btn) btn.innerHTML = originalContent;
                if (data && data.length > 0) {
                    maps[type].flyTo([data[0].lat, data[0].lon], 14);
                    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-check-circle me-1 text-success"></i> Location found: ' + data[0].display_name.split(',')[0];
                } else {
                    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Location not found via search.';
                }
            })
            .catch(err => {
                console.error('Search error:', err);
                if (btn) btn.innerHTML = originalContent;
                if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Error searching location.';
            });
    }

    function locateUser(type) {
        const statusEl = document.getElementById(type + '_map_status');
        if (!navigator.geolocation) {
            statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Geolocation not supported.';
            return;
        }
        const btn = document.querySelector(`#${type}_map_container .btn-outline-secondary`);
        const originalContent = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Locating you...';
        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (btn) btn.innerHTML = originalContent;
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                if (accuracy > 200) {
                    statusEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Low accuracy (~${Math.round(accuracy)}m). Use GPS or search manually.`;
                    return;
                }
                maps[type].flyTo([lat, lon], 17);
                L.circle([lat, lon], { radius: accuracy, color: '#3b82f6', weight: 1, opacity: 0.4, fillOpacity: 0.1 }).addTo(maps[type]);
                L.circleMarker([lat, lon], {
                    radius: 8, fillColor: "#3b82f6", color: "#fff", weight: 2, opacity: 1, fillOpacity: 1
                }).addTo(maps[type]).bindPopup(`You are here (Accuracy: ${Math.round(accuracy)}m)`).openPopup();
                statusEl.innerHTML = `<i class="fa-solid fa-check-circle me-1 text-success"></i> Found you! (Accuracy: ${Math.round(accuracy)}m)`;
            },
            (error) => {
                if (btn) btn.innerHTML = originalContent;
                let msg = 'Unable to retrieve location.';
                if (error.code === 1) msg = 'Location permission denied.';
                else if (error.code === 2) msg = 'GPS signal unavailable.';
                else if (error.code === 3) msg = 'Location request timed out.';
                statusEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> ${msg}`;
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function toggleFullScreenMap(type) {
        const mapDiv = document.getElementById(type + '_map');
        const isFullscreen = mapDiv.classList.contains('map-fullscreen');
        if (!isFullscreen) {
            mapDiv.classList.add('map-fullscreen');
            document.documentElement.classList.add('map-fullscreen-active');
            document.body.classList.add('map-fullscreen-active');
            const exitBtn = document.createElement('button');
            exitBtn.innerHTML = '<i class="fa-solid fa-compress me-1"></i> Exit Full Screen';
            exitBtn.className = 'btn btn-light shadow-sm fw-bold position-absolute top-0 end-0 m-3';
            exitBtn.id = type + '_exit_fs_btn';
            exitBtn.style.zIndex = 10020;
            exitBtn.onclick = (e) => { e.stopPropagation(); toggleFullScreenMap(type); };
            mapDiv.appendChild(exitBtn);

            const searchContainer = document.createElement('div');
            searchContainer.id = type + '_fs_search_container';
            searchContainer.className = 'map-fs-toolbar';

            const input = document.createElement('input');
            input.type = 'text'; input.className = 'form-control shadow-sm map-fs-input'; input.placeholder = 'Search location...';
            input.onkeypress = (e) => { e.stopPropagation(); if (e.key === 'Enter') { e.preventDefault(); searchLocation(type, input.value); } };
            input.onclick = (e) => e.stopPropagation();
            input.onmousedown = (e) => e.stopPropagation();

            const createFsBtn = (icon, cls, title, onClick) => {
                const b = document.createElement('button');
                b.className = `btn ${cls} shadow-sm map-fs-btn`; b.innerHTML = icon; b.title = title;
                b.onclick = (e) => { e.preventDefault(); e.stopPropagation(); onClick(); };
                b.onmousedown = (e) => e.stopPropagation();
                return b;
            };

            searchContainer.appendChild(input);
            searchContainer.appendChild(createFsBtn('<i class="fa-solid fa-magnifying-glass"></i>', 'btn-primary', 'Search', () => searchLocation(type, input.value)));
            searchContainer.appendChild(createFsBtn('<i class="fa-solid fa-plus"></i>', 'btn-light text-dark', 'Zoom In', () => maps[type].zoomIn()));
            searchContainer.appendChild(createFsBtn('<i class="fa-solid fa-minus"></i>', 'btn-light text-dark', 'Zoom Out', () => maps[type].zoomOut()));
            searchContainer.appendChild(createFsBtn('<i class="fa-solid fa-rotate-left"></i>', 'btn-danger text-white', 'Reset Pins', () => { if(confirm('Clear all pins?')) resetMap(type); }));
            mapDiv.appendChild(searchContainer);
        } else {
            mapDiv.classList.remove('map-fullscreen');
            document.documentElement.classList.remove('map-fullscreen-active');
            document.body.classList.remove('map-fullscreen-active');
            const exitBtn = document.getElementById(type + '_exit_fs_btn');
            if (exitBtn) exitBtn.remove();
            const searchContainer = document.getElementById(type + '_fs_search_container');
            if (searchContainer) searchContainer.remove();
        }
        setTimeout(() => { if(maps[type]) maps[type].invalidateSize(); }, 200);
    }
</script>
@endpush

@endsection
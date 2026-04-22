@extends('layouts.dashboard')

@section('sidebar')
    @if(Auth::user()->role === 'admin')
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high me-2"></i> Admin Dashboard</a></li>
    @elseif(Auth::user()->role === 'organizer')
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high me-2"></i> Organizer Dashboard</a></li>
    @else
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high me-2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="#"><i class="fa-solid fa-calendar-check me-2"></i> My Events</a></li>
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="#"><i class="fa-solid fa-medal me-2"></i> Achievements</a></li>
    @endif
    <li class="nav-item mt-3 border-top pt-3">
        <a class="nav-link active fw-bold text-primary" href="{{ route('dashboard.profile') }}">
            <i class="fa-solid fa-user me-2"></i> Profile
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="{{ route('dashboard.settings') }}">
            <i class="fa-solid fa-gear me-2"></i> Settings
        </a>
    </li>
@endsection

@section('headerTitle', 'My Profile')
@section('header-actions')
    <div class="d-none d-md-block">
        <a href="{{ route('dashboard') }}" class="btn btn-sm fw-bold rounded-pill px-4" style="background: var(--rc-surface-2); color: var(--rc-text-muted); border: 1px solid var(--rc-border);">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>
@endsection

@section('content')
@php
    $role        = $user->role;
    $accentColor = $role === 'admin' ? '#ffc107' : ($role === 'organizer' ? '#a855f7' : '#1aad6e');
    $accentRgb   = $role === 'admin' ? '255,193,7' : ($role === 'organizer' ? '168,85,247' : '26,173,110');
    $accentIcon  = $role === 'admin' ? 'fa-shield-halved' : ($role === 'organizer' ? 'fa-building' : 'fa-person-running');
    $roleLabel   = $role === 'admin' ? 'Administrator' : ($role === 'organizer' ? 'Event Organizer' : 'Runner');

    $fl = $user->runnerProfile->fitness_level ?? 'beginner';
    $flRgb = match(strtolower($fl)) {
        'beginner' => '26,173,110', 'improving' => '13,202,240',
        'intermediate' => '255,193,7', default => '107,114,128'
    };
    $flColor = match(strtolower($fl)) {
        'beginner' => '#1aad6e', 'improving' => '#0dcaf0',
        'intermediate' => '#ffc107', default => '#6b7280'
    };
    $flIcon = match(strtolower($fl)) {
        'beginner' => 'fa-seedling', 'improving' => 'fa-chart-line',
        'intermediate' => 'fa-fire', default => 'fa-signal'
    };
@endphp

{{-- ══ Role-adaptive CSS ══ --}}
<style>
    .profile-hero-card {
        background: var(--rc-surface);
        border: 1px solid var(--rc-border);
        border-radius: 24px;
        overflow: hidden;
        position: relative;
    }
    .profile-banner {
        height: 110px;
        background: linear-gradient(135deg,
            rgba({{ $accentRgb }}, 0.22) 0%,
            rgba({{ $accentRgb }}, 0.06) 60%,
            transparent 100%);
        position: relative;
    }
    .profile-banner-pattern {
        position: absolute; inset: 0;
        background-image: radial-gradient(circle, rgba({{ $accentRgb }}, 0.06) 1px, transparent 1px);
        background-size: 20px 20px;
    }
    .profile-watermark {
        position: absolute; bottom: -12px; right: 16px;
        font-size: 7rem; opacity: 0.07;
        color: {{ $accentColor }}; pointer-events: none;
    }
    .profile-avatar {
        width: 96px; height: 96px; border-radius: 50%;
        background: linear-gradient(135deg, {{ $accentColor }}, rgba({{ $accentRgb }}, 0.5));
        display: flex; align-items: center; justify-content: center;
        font-size: 2.3rem; font-weight: 800; color: #fff;
        border: 4px solid var(--rc-bg);
        box-shadow: 0 0 0 2px rgba({{ $accentRgb }}, 0.35), 0 8px 30px rgba({{ $accentRgb }}, 0.2);
        text-shadow: 0 2px 8px rgba(0,0,0,0.5);
        margin-top: -48px; position: relative; z-index: 2;
    }
    .profile-online-dot {
        position: absolute; bottom: 4px; right: 4px;
        width: 16px; height: 16px; background: #1aad6e;
        border-radius: 50%; border: 3px solid var(--rc-bg); z-index: 3;
        animation: pulse-pd 2s ease-in-out infinite;
    }
    @keyframes pulse-pd {
        0%,100% { box-shadow: 0 0 0 0 rgba(26,173,110,0.4); }
        50%      { box-shadow: 0 0 0 5px rgba(26,173,110,0); }
    }
    .role-badge {
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba({{ $accentRgb }}, 0.12);
        color: {{ $accentColor }};
        border: 1px solid rgba({{ $accentRgb }}, 0.25);
        border-radius: 99px; padding: 4px 12px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;
    }
    .profile-info-card {
        background: var(--rc-surface-2);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 14px; padding: 14px 18px;
        transition: border-color .18s, background .18s;
        height: 100%;
    }
    .profile-info-card:hover {
        border-color: rgba({{ $accentRgb }}, 0.2);
        background: rgba({{ $accentRgb }}, 0.03);
    }
    .profile-info-label {
        font-size: 0.63rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.8px; color: rgba({{ $accentRgb }}, 0.65);
        margin-bottom: 5px;
    }
    .profile-info-value {
        font-size: 0.9rem; font-weight: 600;
        color: var(--rc-text); word-break: break-all; line-height: 1.4;
    }
    .profile-section-head {
        font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; color: rgba({{ $accentRgb }}, 0.6);
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 18px; padding-bottom: 10px;
        border-bottom: 1px solid rgba({{ $accentRgb }}, 0.12);
    }
    .profile-section-head::before {
        content: ''; width: 3px; height: 14px; background: {{ $accentColor }};
        border-radius: 99px; flex-shrink: 0;
    }
    .edit-btn {
        background: rgba({{ $accentRgb }}, 0.12) !important;
        color: {{ $accentColor }} !important;
        border: 1px solid rgba({{ $accentRgb }}, 0.3) !important;
        border-radius: 50px !important; font-weight: 700 !important;
        padding: 9px 24px !important; font-size: 0.85rem !important;
        transition: all .2s !important;
    }
    .edit-btn:hover {
        background: rgba({{ $accentRgb }}, 0.22) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba({{ $accentRgb }}, 0.2) !important;
    }
    .delete-btn {
        background: rgba(248,81,73,0.08) !important; color: #f87171 !important;
        border: 1px solid rgba(248,81,73,0.2) !important;
        border-radius: 50px !important; font-weight: 700 !important;
        padding: 9px 24px !important; font-size: 0.85rem !important;
        transition: all .2s !important;
    }
    .delete-btn:hover {
        background: rgba(248,81,73,0.18) !important;
        transform: translateY(-2px);
    }
    .fitness-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 14px; border-radius: 99px;
        font-size: 0.78rem; font-weight: 700;
    }
    .health-chip {
        background: rgba(255,193,7,0.1); color: #ffc107;
        border: 1px solid rgba(255,193,7,0.2);
        border-radius: 20px; padding: 3px 12px;
        font-size: 0.72rem; font-weight: 600;
    }
    /* Edit Modal Styles */
    #editProfileModal .modal-content {
        background: var(--rc-surface);
        border: 1px solid var(--rc-border); border-radius: 20px;
    }
    #editProfileModal .modal-header {
        background: linear-gradient(135deg, rgba({{ $accentRgb }}, 0.14), rgba({{ $accentRgb }}, 0.04));
        border-bottom: 1px solid rgba({{ $accentRgb }}, 0.15) !important;
        border-radius: 20px 20px 0 0; padding: 20px 24px;
    }
    #editProfileModal .modal-header .modal-title { color: {{ $accentColor }}; }
    #editProfileModal .modal-header .btn-close { filter: invert(1) opacity(0.6); }
    #editProfileModal .modal-body { background: var(--rc-surface); padding: 24px; }
    #editProfileModal .modal-footer {
        background: var(--rc-surface-2); border-radius: 0 0 20px 20px;
        border-top: 1px solid var(--rc-border) !important; padding: 16px 24px;
    }
    #editProfileModal .modal-footer,
    #deleteAccountModal .modal-footer {
        display: flex;
        gap: 10px;
        align-items: stretch;
    }
    #editProfileModal .modal-footer > .btn,
    #editProfileModal .modal-footer > form,
    #deleteAccountModal .modal-footer > .btn,
    #deleteAccountModal .modal-footer > form {
        flex: 1 1 0;
        margin: 0;
    }
    #editProfileModal .modal-footer > form .btn,
    #deleteAccountModal .modal-footer > form .btn {
        width: 100%;
    }
    @media (min-width: 768px) {
        #deleteAccountModal .modal-footer {
            justify-content: center;
            flex-wrap: nowrap;
        }
        #deleteAccountModal .modal-footer > .btn,
        #deleteAccountModal .modal-footer > form {
            flex: 1 1 0;
            max-width: 220px;
        }
        #deleteAccountModal .modal-footer > .btn {
            width: 100%;
            min-width: 0;
        }
        #deleteAccountModal .modal-footer > form .btn {
            width: 100%;
            min-width: 0;
        }
    }
    @media (max-width: 767.98px) {
        #deleteAccountModal .modal-footer {
            flex-direction: column;
            align-items: stretch;
        }
        #deleteAccountModal .modal-footer > .btn,
        #deleteAccountModal .modal-footer > form,
        #deleteAccountModal .modal-footer > form .btn {
            width: 100%;
            max-width: 100%;
        }
    }
    #editProfileModal .form-control,
    #editProfileModal .form-select {
        background: var(--rc-surface-2) !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        color: rgba(255,255,255,0.92) !important; border-radius: 10px !important;
        padding: 10px 14px !important; font-size: 0.88rem !important;
    }
    #editProfileModal .form-control:focus,
    #editProfileModal .form-select:focus {
        border-color: rgba({{ $accentRgb }}, 0.4) !important;
        box-shadow: 0 0 0 3px rgba({{ $accentRgb }}, 0.1) !important;
    }
    #editProfileModal .form-control::placeholder { color: rgba(255,255,255,0.48) !important; }
    #editProfileModal label.form-label {
        color: rgba(255,255,255,0.72); font-size: 0.68rem;
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px;
        margin-bottom: 6px;
    }
    #editProfileModal .form-control:-webkit-autofill,
    #editProfileModal .form-control:-webkit-autofill:hover,
    #editProfileModal .form-control:-webkit-autofill:focus {
        -webkit-text-fill-color: rgba(255,255,255,0.92);
        box-shadow: 0 0 0 1000px var(--rc-surface-2) inset;
        transition: background-color 5000s ease-in-out 0s;
    }
    #editProfileModal .ts-wrapper.single .ts-control,
    #editProfileModal .ts-wrapper.single.input-active .ts-control {
        background: var(--rc-surface-2) !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        box-shadow: none !important;
        border-radius: 10px !important;
        min-height: 42px;
    }
    #editProfileModal .ts-control > .item,
    #editProfileModal .ts-control input {
        color: rgba(255,255,255,0.92) !important;
    }
    #editProfileModal .ts-control input::placeholder {
        color: rgba(255,255,255,0.5) !important;
    }
    #editProfileModal .ts-dropdown,
    #editProfileModal .ts-dropdown.single {
        background: #162031 !important;
        border: 1px solid rgba(255,255,255,0.12) !important;
        border-radius: 10px !important;
        color: rgba(255,255,255,0.9) !important;
        z-index: 2000;
    }
    #editProfileModal .ts-dropdown .option {
        color: rgba(255,255,255,0.88) !important;
        background: transparent;
    }
    #editProfileModal .ts-dropdown .option:hover,
    #editProfileModal .ts-dropdown .active {
        background: rgba({{ $accentRgb }}, 0.22) !important;
        color: #ffffff !important;
    }
    #editProfileModal .condition-card {
        background: var(--rc-surface-2); border: 1px solid rgba(255,255,255,0.06);
        border-radius: 10px; padding: 11px 14px; cursor: pointer;
        display: flex; align-items: center; justify-content: space-between;
        transition: all .15s;
    }
    #editProfileModal .condition-card:hover {
        border-color: rgba({{ $accentRgb }}, 0.25);
        background: rgba({{ $accentRgb }}, 0.05);
    }
    #editProfileModal .condition-card input[type="checkbox"] {
        width: 1.1em; height: 1.1em; accent-color: {{ $accentColor }};
    }
    #deleteAccountModal .modal-content {
        background: var(--rc-surface); border: 1px solid var(--rc-border); border-radius: 20px;
    }
    #deleteAccountModal .modal-header {
        background: rgba(248,81,73,0.06); border-radius: 20px 20px 0 0;
        border-bottom: 1px solid rgba(248,81,73,0.12) !important;
    }
    #deleteAccountModal .btn-close { filter: invert(1) opacity(0.6); }
    #deleteAccountModal .modal-footer {
        background: var(--rc-surface-2); border-radius: 0 0 20px 20px;
        border-top: 1px solid var(--rc-border) !important;
    }
</style>

<div class="row g-4 justify-content-center">

    {{-- ── Left: Profile Hero Card ── --}}
    <div class="col-lg-4 col-xl-3">
        <div class="profile-hero-card">
            <div class="profile-banner">
                <div class="profile-banner-pattern"></div>
                <div class="profile-watermark"><i class="fa-solid {{ $accentIcon }}"></i></div>
            </div>
            <div class="px-4 pb-4">
                <div class="d-flex align-items-end justify-content-between" style="margin-top: 8px; margin-bottom: 16px;">
                    <div style="position: relative; display: inline-block;">
                        <div class="profile-avatar">{{ strtoupper(substr($user->username, 0, 1)) }}</div>
                        <span class="profile-online-dot"></span>
                    </div>
                    <span class="role-badge mb-1">
                        <i class="fa-solid {{ $accentIcon }}" style="font-size: 0.6rem;"></i> {{ $roleLabel }}
                    </span>
                </div>

                <h4 class="fw-bold mb-0" style="color: var(--rc-text); font-size: 1.1rem; letter-spacing: -0.2px;">{{ $user->username }}</h4>
                <p class="mb-0 mt-1" style="color: var(--rc-text-muted); font-size: 0.8rem;">{{ $user->email }}</p>

                <div class="mt-4 d-flex flex-column gap-2" style="font-size: 0.8rem;">
                    <div class="d-flex align-items-center gap-2" style="color: rgba(255,255,255,0.4);">
                        <i class="fa-regular fa-calendar" style="color: {{ $accentColor }}; width: 14px;"></i>
                        Joined {{ $user->created_at->format('M Y') }}
                    </div>
                    @if($user->runnerProfile)
                        <div class="d-flex align-items-center gap-2" style="color: rgba(255,255,255,0.4);">
                            <i class="fa-solid fa-cake-candles" style="color: {{ $accentColor }}; width: 14px;"></i>
                            {{ $user->runnerProfile->age ?? '—' }} yrs · {{ ucfirst($user->runnerProfile->gender ?? 'N/A') }}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid {{ $flIcon }}" style="color: {{ $flColor }}; width: 14px;"></i>
                            <span class="fitness-badge" style="background: rgba({{ $flRgb }}, 0.15); color: {{ $flColor }}; border: 1px solid rgba({{ $flRgb }}, 0.3); padding: 2px 10px; font-size: 0.72rem;">
                                {{ ucfirst($fl) }}
                            </span>
                        </div>
                    @elseif($user->organizerProfile)
                        <div class="d-flex align-items-center gap-2" style="color: rgba(255,255,255,0.4);">
                            <i class="fa-solid fa-building" style="color: {{ $accentColor }}; width: 14px;"></i>
                            {{ Str::limit($user->organizerProfile->organization_name, 25) }}
                        </div>

                    @elseif($role === 'admin')
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-check" style="color: #1aad6e; width: 14px;"></i>
                            <span style="color: #1aad6e; font-weight: 600;">Full System Access</span>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column gap-2 mt-4">
                    @if($user->runnerProfile || $user->organizerProfile)
                    <button class="btn edit-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Edit Profile
                    </button>
                    @endif
                    <button class="btn delete-btn" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fa-solid fa-trash-alt me-2"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right: Details ── --}}
    <div class="col-lg-8 col-xl-9">

        {{-- Account Info --}}
        <div class="profile-hero-card p-4 mb-4">
            <div class="profile-section-head"><i class="fa-solid fa-id-card me-1"></i> Account Information</div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-at me-1"></i> Username</div>
                        <div class="profile-info-value">{{ $user->username }}</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-envelope me-1"></i> Email</div>
                        <div class="profile-info-value">{{ $user->email }}</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-user-tag me-1"></i> Role</div>
                        <div class="profile-info-value">
                            <span class="role-badge"><i class="fa-solid {{ $accentIcon }}" style="font-size: 0.6rem;"></i> {{ $roleLabel }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-regular fa-calendar me-1"></i> Member Since</div>
                        <div class="profile-info-value">{{ $user->created_at->format('F d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Runner Profile --}}
        @if($user->runnerProfile)
        <div class="profile-hero-card p-4">
            <div class="profile-section-head"><i class="fa-solid fa-person-running me-1"></i> Runner Profile</div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-user me-1"></i> Full Name</div>
                        <div class="profile-info-value">
                            {{ trim($user->runnerProfile->first_name . ' ' . $user->runnerProfile->middle_name . ' ' . $user->runnerProfile->last_name . ' ' . $user->runnerProfile->name_extension) }}
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-cake-candles me-1"></i> Age</div>
                        <div class="profile-info-value">{{ $user->runnerProfile->age ?? 'N/A' }} yrs</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-venus-mars me-1"></i> Gender</div>
                        <div class="profile-info-value">{{ ucfirst($user->runnerProfile->gender ?? 'N/A') }}</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-signal me-1"></i> Fitness Level</div>
                        <div class="profile-info-value">
                            <span class="fitness-badge" style="background: rgba({{ $flRgb }}, 0.15); color: {{ $flColor }}; border: 1px solid rgba({{ $flRgb }}, 0.3);">
                                <i class="fa-solid {{ $flIcon }}" style="font-size: 0.65rem;"></i> {{ ucfirst($fl) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-phone me-1"></i> Phone</div>
                        <div class="profile-info-value">{{ $user->runnerProfile->phone_number ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-location-dot me-1"></i> Address</div>
                        <div class="profile-info-value" style="font-size: 0.82rem; line-height: 1.5;">{{ $user->runnerProfile->address ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-heart-pulse me-1"></i> Health Conditions</div>
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            @if($user->runnerProfile->health_conditions && count($user->runnerProfile->health_conditions) > 0)
                                @foreach($user->runnerProfile->health_conditions as $condition)
                                    <span class="health-chip">{{ $condition }}</span>
                                @endforeach
                            @else
                                <span style="color: rgba(255,255,255,0.25); font-size: 0.82rem;">No conditions listed</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Organizer Profile --}}
        @elseif($user->organizerProfile)
        <div class="profile-hero-card p-4">
            <div class="profile-section-head"><i class="fa-solid fa-building me-1"></i> Organizer Profile</div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-building me-1"></i> Organization Name</div>
                        <div class="profile-info-value d-flex flex-wrap align-items-center gap-2">
                            {{ $user->organizerProfile->organization_name }}

                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-phone me-1"></i> Phone Number</div>
                        <div class="profile-info-value">{{ $user->organizerProfile->phone_number ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card">
                        <div class="profile-info-label"><i class="fa-solid fa-location-dot me-1"></i> Address</div>
                        <div class="profile-info-value" style="font-size: 0.82rem; line-height: 1.5;">{{ $user->organizerProfile->address ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Admin - No extended profile --}}
        @elseif($role === 'admin')
        <div class="profile-hero-card p-4">
            <div class="profile-section-head"><i class="fa-solid fa-shield-halved me-1"></i> Administrator Access</div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="profile-info-card" style="border-color: rgba(255,193,7,0.12);">
                        <div class="profile-info-label"><i class="fa-solid fa-users-gear me-1"></i> Access Level</div>
                        <div class="profile-info-value">Full System Admin</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="profile-info-card" style="border-color: rgba(255,193,7,0.12);">
                        <div class="profile-info-label"><i class="fa-solid fa-shield-check me-1"></i> Permissions</div>
                        <div class="profile-info-value">Unrestricted</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="profile-info-card text-center py-5" style="border: 1px dashed rgba(255,193,7,0.12);">
                        <div style="font-size: 3.5rem; opacity: 0.1; color: #ffc107; margin-bottom: 10px;"><i class="fa-solid fa-shield-halved"></i></div>
                        <p style="color: rgba(255,255,255,0.25); font-size: 0.82rem; margin: 0;">
                            Administrator accounts have full platform access.<br>Contact the developer to update admin credentials.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- end right col --}}
</div>{{-- end row --}}

{{-- ═══ Edit Profile Modal ═══ --}}
@if($user->runnerProfile || $user->organizerProfile)
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i> Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('dashboard.profile.update') }}" method="POST" id="editProfileForm">
                    @csrf
                    @method('PUT')

                    @if($user->runnerProfile)
                        <p class="mb-4" style="color: rgba(255,255,255,0.3); font-size: 0.78rem; padding-left: 12px; border-left: 2px solid {{ $accentColor }};">Personal Information</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="{{ $user->runnerProfile->first_name }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name" value="{{ $user->runnerProfile->middle_name }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="{{ $user->runnerProfile->last_name }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Extension</label>
                                <input type="text" class="form-control" name="name_extension" value="{{ $user->runnerProfile->name_extension }}" placeholder="Jr., Sr.">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Age</label>
                                <input type="number" class="form-control" name="age" min="10" max="120" value="{{ $user->runnerProfile->age }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender</label>
                                <select class="form-control" name="gender" required>
                                    <option value="male" {{ $user->runnerProfile->gender == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ $user->runnerProfile->gender == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ $user->runnerProfile->gender == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone_number" value="{{ $user->runnerProfile->phone_number }}" required>
                            </div>
                        </div>

                        <p class="mb-3" style="color: rgba(255,255,255,0.3); font-size: 0.78rem; padding-left: 12px; border-left: 2px solid {{ $accentColor }};">Address Details</p>
                        <input type="hidden" name="address" id="edit_full_address" value="{{ $user->runnerProfile->address }}" required>
                        <p style="font-size: 0.78rem; color: rgba(255,255,255,0.3); margin-bottom: 10px;">
                            <i class="fa-solid fa-location-dot me-1" style="color: {{ $accentColor }};"></i>
                            Current: <span style="color: rgba(255,255,255,0.55);">{{ $user->runnerProfile->address }}</span>
                        </p>
                        <div class="row g-2 mb-4">
                            <div class="col-md-6"><select class="form-control" id="edit_city"><option value="">Update City / Municipality</option></select></div>
                            <div class="col-md-6"><select class="form-control" id="edit_brgy" disabled><option value="">Update Barangay</option></select></div>
                            <div class="col-12"><input type="text" class="form-control" id="edit_street" placeholder="Update Purok / Street / Subdivision"></div>
                            <div class="col-12"><small style="color: rgba(255,255,255,0.2);"><i class="fa-solid fa-circle-info me-1" style="color: {{ $accentColor }};"></i> Leave blank to keep current address.</small></div>
                        </div>

                        <p class="mb-3" style="color: rgba(255,255,255,0.3); font-size: 0.78rem; padding-left: 12px; border-left: 2px solid {{ $accentColor }};">Health Conditions</p>
                        @php
                            $conditions = ['None','Asthma','Heart Condition','High Blood Pressure','Joint Problems','Diabetes','Recent Injury','Other'];
                            $userConditions = $user->runnerProfile->health_conditions ?? [];
                            $otherText = ''; $hasOther = false;
                            foreach ($userConditions as $c) {
                                if (str_starts_with($c, 'Other: ')) { $hasOther = true; $otherText = substr($c, 7); }
                                elseif ($c === 'Other') { $hasOther = true; }
                            }
                        @endphp
                        <div class="row g-2 mb-3">
                            @foreach($conditions as $condition)
                            <div class="col-md-6">
                                <label class="condition-card" for="edit_cond_{{ Str::slug($condition) }}" style="cursor:pointer; margin:0;">
                                    <span class="fw-semibold" style="color: rgba(255,255,255,0.75); font-size: 0.85rem;">{{ $condition }}</span>
                                    <input class="form-check-input" type="checkbox" name="health_conditions[]" value="{{ $condition }}"
                                        id="edit_cond_{{ Str::slug($condition) }}"
                                        {{ in_array($condition, $userConditions) || ($condition === 'Other' && $hasOther) ? 'checked' : '' }}>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div id="editOtherConditionInput" class="mt-2 {{ $hasOther ? '' : 'd-none' }}">
                            <label class="form-label">Please Specify Condition</label>
                            <input type="text" class="form-control" name="other_condition_text" value="{{ $otherText }}" placeholder="e.g. Mild Arthritis">
                        </div>

                    @elseif($user->organizerProfile)
                        <p class="mb-3" style="color: rgba(255,255,255,0.3); font-size: 0.78rem; padding-left: 12px; border-left: 2px solid {{ $accentColor }};">Organizer Information</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Organization Name</label>
                                <input type="text" class="form-control" name="organization_name" value="{{ $user->organizerProfile->organization_name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone_number" value="{{ $user->organizerProfile->phone_number }}" required>
                            </div>
                        </div>
                        <p class="mb-3" style="color: rgba(255,255,255,0.3); font-size: 0.78rem; padding-left: 12px; border-left: 2px solid {{ $accentColor }};">Address Details</p>
                        <input type="hidden" name="address" id="edit_full_address" value="{{ $user->organizerProfile->address }}" required>
                        <p style="font-size: 0.78rem; color: rgba(255,255,255,0.3); margin-bottom: 10px;">
                            <i class="fa-solid fa-location-dot me-1" style="color: {{ $accentColor }};"></i>
                            Current: <span style="color: rgba(255,255,255,0.55);">{{ $user->organizerProfile->address }}</span>
                        </p>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><select class="form-control" id="edit_city"><option value="">Update City / Municipality</option></select></div>
                            <div class="col-md-6"><select class="form-control" id="edit_brgy" disabled><option value="">Update Barangay</option></select></div>
                            <div class="col-12"><input type="text" class="form-control" id="edit_street" placeholder="Update Purok / Street / Subdivision"></div>
                            <div class="col-12"><small style="color: rgba(255,255,255,0.2);"><i class="fa-solid fa-circle-info me-1" style="color: {{ $accentColor }};"></i> Leave blank to keep current address.</small></div>
                        </div>
                    @endif
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn fw-bold rounded-pill px-4" style="background: var(--rc-surface-3); color: var(--rc-text-muted); border: none;" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editProfileForm" class="btn fw-bold rounded-pill px-5" style="background: {{ $accentColor }}; color: #0d1117; border: none; box-shadow: 0 4px 20px rgba({{ $accentRgb }}, 0.3);">
                    <i class="fa-solid fa-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Other condition toggle
    const otherCb = document.getElementById('edit_cond_other');
    const otherDiv = document.getElementById('editOtherConditionInput');
    if (otherCb && otherDiv) {
        otherCb.addEventListener('change', function () {
            otherDiv.classList.toggle('d-none', !this.checked);
            if (this.checked) otherDiv.querySelector('input')?.focus();
        });
    }

    // Phone formatter
    const phoneInput = document.querySelector('input[name="phone_number"]');
    if (phoneInput) {
        const fmt = v => {
            let c = v.replace(/\D/g,'');
            if (c.startsWith('63')) c = '0'+c.substring(2);
            if (c.startsWith('0') && c.length===11)
                return `+63 ${c.substring(1,4)} ${c.substring(4,7)} ${c.substring(7,11)}`;
            return v;
        };
        phoneInput.addEventListener('blur', e => e.target.value = fmt(e.target.value));
    }

    // Address dropdowns
    const cityEl  = document.getElementById('edit_city');
    const brgyEl  = document.getElementById('edit_brgy');
    const stEl    = document.getElementById('edit_street');
    const addrEl  = document.getElementById('edit_full_address');
    const origAddr = @json($user->runnerProfile ? $user->runnerProfile->address : ($user->organizerProfile ? $user->organizerProfile->address : ''));

    if (cityEl && typeof TomSelect !== 'undefined') {
        const tsCity = new TomSelect(cityEl, { create:false, maxOptions:null, placeholder:'Search city...' });
        const tsBrgy = new TomSelect(brgyEl, { create:false, maxOptions:null, placeholder:'Search barangay...' });

        fetch("{{ route('locations.cities') }}")
            .then(r => r.json())
            .then(data => tsCity.addOptions(data.map(c => ({
                value: c.citymunCode,
                text: c.citymunDesc + (c.provDesc && c.provDesc!==c.citymunDesc ? ', '+c.provDesc : ''),
                name: c.citymunDesc, prov: c.provDesc, reg: c.regDesc
            }))));

        tsCity.on('change', val => {
            tsBrgy.clearOptions(); tsBrgy.clear(); tsBrgy.disable();
            if (val) {
                fetch("{{ route('locations.barangays') }}?city_code="+val)
                    .then(r => r.json())
                    .then(data => {
                        tsBrgy.addOptions(data.map(b=>({value:b.brgyCode,text:b.brgyDesc,name:b.brgyDesc})));
                        tsBrgy.enable();
                    });
            }
            upd();
        });
        tsBrgy.on('change', upd);
        if (stEl) stEl.addEventListener('input', upd);

        function upd() {
            const cO = tsCity.options[tsCity.getValue()];
            const bO = tsBrgy.options[tsBrgy.getValue()];
            const st = stEl ? stEl.value.trim() : '';
            if (!cO && !bO && !st) { addrEl.value = origAddr; return; }
            const p = [];
            if (st) p.push(st);
            if (bO) p.push('Brgy. '+bO.name);
            if (cO) { p.push(cO.name); if (cO.prov && cO.prov!==cO.name) p.push(cO.prov); if (cO.reg) p.push(cO.reg); }
            addrEl.value = p.join(', ');
        }
    }
});
</script>
@endpush
@endif

{{-- ═══ Delete Account Modal ═══ --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: #f87171;"><i class="fa-solid fa-triangle-exclamation me-2"></i> Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-4" style="color: #f87171; opacity: 0.55;"><i class="fa-solid fa-user-slash fa-4x"></i></div>
                <h5 class="fw-bold mb-2" style="color: var(--rc-text);">Are you absolutely sure?</h5>
                <p style="color: var(--rc-text-muted); font-size: 0.88rem;">This will permanently delete your account and all data. <strong style="color: #f87171;">This cannot be undone.</strong></p>
            </div>
            <div class="modal-footer justify-content-center pb-4">
                <button type="button" class="btn fw-bold rounded-pill px-5" style="background: var(--rc-surface-3); color: var(--rc-text-muted); border: none;" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('dashboard.profile.delete') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger fw-bold rounded-pill px-5">
                        <i class="fa-solid fa-trash me-1"></i> Delete My Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
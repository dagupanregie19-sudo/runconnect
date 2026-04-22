@extends('layouts.dashboard')

@section('sidebar')
    @if(Auth::user()->role === 'admin')
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="{{ route('dashboard') }}"><i
                    class="fa-solid fa-gauge-high me-2"></i> Admin Dashboard</a></li>
    @else
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="{{ route('dashboard') }}"><i
                    class="fa-solid fa-gauge-high me-2"></i> User Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="#"><i
                    class="fa-solid fa-calendar-check me-2"></i> My Events</a></li>
        <li class="nav-item"><a class="nav-link text-muted hover-primary" href="#"><i class="fa-solid fa-medal me-2"></i>
                Achievements</a></li>
    @endif
    <li class="nav-item mt-3 border-top pt-3">
        <a class="nav-link text-muted hover-primary" href="{{ route('dashboard.profile') }}">
            <i class="fa-solid fa-user me-2"></i> Profile
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-bold text-primary" href="{{ route('dashboard.settings') }}">
            <i class="fa-solid fa-gear me-2"></i> Settings
        </a>
    </li>
@endsection

@section('headerTitle', 'Account Settings')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-5">
                    <form>
                        <h5 class="fw-bold mb-4 text-secondary border-bottom pb-2"><i class="fa-solid fa-bell me-2"></i>
                            Notifications</h5>
                        <div class="form-check form-switch mb-3 custom-switch">
                            <input class="form-check-input" type="checkbox" id="emailNotifs" checked>
                            <label class="form-check-label fw-bold" for="emailNotifs">Email Notifications</label>
                            <div class="small text-muted ps-0">Receive updates about your registration status and events.
                            </div>
                        </div>
                        <div class="form-check form-switch mb-3 custom-switch">
                            <input class="form-check-input" type="checkbox" id="pushNotifs" checked>
                            <label class="form-check-label fw-bold" for="pushNotifs">Push Notifications</label>
                            <div class="small text-muted ps-0">Get real-time alerts on your device.</div>
                        </div>

                        <hr class="my-5 border-secondary-subtle">

                        <h5 class="fw-bold mb-4 text-secondary border-bottom pb-2"><i class="fa-solid fa-lock me-2"></i>
                            Security</h5>
                        <div class="d-grid gap-3 d-md-flex">
                            <button
                                class="btn btn-outline-danger btn-sm rounded-pill fw-bold text-start px-4 py-2 hover-bg-danger hover-text-white transition-all"><i
                                    class="fa-solid fa-key me-2"></i> Change Password</button>
                            <button
                                class="btn btn-outline-danger btn-sm rounded-pill fw-bold text-start px-4 py-2 hover-bg-danger hover-text-white transition-all"><i
                                    class="fa-solid fa-shield-halved me-2"></i> Enable 2FA</button>
                        </div>

                        <hr class="my-5 border-secondary-subtle">

                        <h5 class="fw-bold mb-4 text-secondary border-bottom pb-2"><i class="fa-solid fa-flag me-2"></i>
                            Preferences</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Language</label>
                            <select class="form-select border-light bg-light rounded-pill ps-3 fw-bold w-auto">
                                <option selected>English (US)</option>
                                <option>Spanish</option>
                                <option>French</option>
                                <option>German</option>
                            </select>
                        </div>
                    </form>

                    <div class="mt-5 text-end border-top pt-4">
                        <button class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
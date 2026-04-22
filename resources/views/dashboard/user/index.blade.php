@extends('layouts.dashboard')

@section('sidebar')
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="#">
            <i class="fa-solid fa-calendar-check me-2"></i> My Events
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="#">
            <i class="fa-solid fa-medal me-2"></i> Achievements
        </a>
    </li>
    <li class="nav-item mt-3 border-top pt-3">
        <a class="nav-link text-muted hover-primary" href="{{ route('dashboard.profile') }}">
            <i class="fa-solid fa-user me-2"></i> Profile
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted hover-primary" href="{{ route('dashboard.settings') }}">
            <i class="fa-solid fa-gear me-2"></i> Settings
        </a>
    </li>
@endsection

@section('headerTitle', 'Welcome back')
@section('header-actions')
@endsection

@section('content')
    <!-- Leaflet Map CSS for Live Monitoring (Loaded Locally) -->
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

    <style>
        .offline-map-msg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 400; /* Leaflet tiles are z-index 200/300 */
            color: #ffffff;
            text-align: center;
            pointer-events: none; /* Let clicks pass through */
        }
    </style>

    @if(auth()->user()->role === 'user')
        <style>
            /* Make dashboard tabs scrollable on narrow mobile screens rather than stacking */
            #dashboardTabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none; /* Auto-hide Firefox */
                border-bottom: 2px solid rgba(255,255,255,0.05); /* Proper dark border */
            }
            #dashboardTabs::-webkit-scrollbar {
                display: none; /* Auto-hide Chrome/Safari */
            }
            #dashboardTabs .nav-item {
                flex: 0 0 auto;
            }
            #dashboardTabs .nav-link {
                color: rgba(255,255,255,0.5);
                border: none;
                border-bottom: 2px solid transparent;
                padding: 12px 20px;
                margin-bottom: -2px; /* Pull down over border */
                transition: all 0.2s;
            }
            #dashboardTabs .nav-link:hover {
                color: rgba(255,255,255,0.8);
                border-bottom-color: rgba(255,255,255,0.2);
            }
            #dashboardTabs .nav-link.active {
                background: transparent;
                color: #1aad6e;
                border-bottom-color: #1aad6e;
            }

            .calendar-day-circle {
                width: 50px;
                height: 50px;
            }
            @media (max-width: 576px) {
                .calendar-day-circle {
                    width: 35px;
                    height: 35px;
                }
            }
        </style>
        <!-- Dashboard Tabs -->
        <ul class="nav nav-tabs mb-4 px-1" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="daily-challenge-tab" data-bs-toggle="tab"
                    data-bs-target="#daily-challenge" type="button" role="tab" aria-controls="daily-challenge"
                    aria-selected="true">
                    <i class="fa-solid fa-fire me-2"></i>Daily Challenge
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button"
                    role="tab" aria-controls="events" aria-selected="false">
                    <i class="fa-solid fa-calendar-day me-2"></i>Events
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold position-relative" id="my-events-tab" data-bs-toggle="tab" data-bs-target="#user-my-events" type="button"
                    role="tab" aria-controls="user-my-events" aria-selected="false">
                    <i class="fa-solid fa-medal me-2"></i>My Events
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold position-relative" id="history-tab" data-bs-toggle="tab" data-bs-target="#user-history" type="button"
                    role="tab" aria-controls="user-history" aria-selected="false">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>History
                </button>
            </li>
        </ul>

        <div class="tab-content" id="dashboardTabsContent">
            <!-- Daily Challenge Tab -->
            <div class="tab-pane fade show active" id="daily-challenge" role="tabpanel" aria-labelledby="daily-challenge-tab">
                @php
                    $currentFitness = $user->runnerProfile->fitness_level ?? 'beginner';
                    $levelOrder = ['beginner', 'improving', 'intermediate'];
                    $currentIndex = array_search($currentFitness, $levelOrder);
                    $activeConfig = $challenge ? $challengeLevels[$challenge->level] : ($challengeLevels[$currentFitness] ?? $challengeLevels['beginner']);

                    // Build logs by date for calendar (all challenge logs for this month)
                    $allLogs = $challenge ? $challenge->logs : collect();
                    $logsByDate = $allLogs->groupBy(fn($l) => $l->logged_date->format('Y-m-d'))
                        ->map(fn($g) => $g->sum('distance_km'));

                    // Calendar month data
                    $calendarDate = now();
                    $daysInMonth = $calendarDate->daysInMonth;
                    $startDayOfWeek = $calendarDate->copy()->startOfMonth()->dayOfWeek;
                    $todayDay = $calendarDate->day;
                    $currentMonth = $calendarDate->month;
                    $currentYear = $calendarDate->year;

                    // If challenge is active, compute progress
                    $progress = $challenge ? $challenge->progress : 0;
                    $daysLeft = $challenge ? $challenge->remaining_days : 0;
                    $isDaily = $challenge && !is_null($challenge->daily_target);
                    $dailyTarget = $challenge ? ($challenge->daily_target ?? 0) : 0;
                    $todayLogged = $challenge ? $challenge->today_logged : 0;
                @endphp

                <div class="row g-4">
                    {{-- ═══ LEFT: Calendar View ═══ --}}
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4" style="background: var(--rc-surface); color: var(--rc-text);">
                            <div class="card-header border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center" style="background: transparent;">
                                <h5 class="fw-bold mb-0" style="color: var(--rc-green);">
                                    <i class="fa-regular fa-calendar me-2"></i>{{ $calendarDate->format('F Y') }}
                                </h5>
                                <div class="d-flex align-items-center gap-2">
                                    {{-- Legend --}}
                                    <span class="d-flex align-items-center gap-1 me-2 small" style="color: var(--rc-text-muted);">
                                        <span class="d-inline-block rounded-circle" style="background: var(--rc-green); width:10px;height:10px;"></span> Done
                                    </span>
                                    <span class="d-flex align-items-center gap-1 small" style="color: var(--rc-text-muted);">
                                        <span class="d-inline-block rounded-circle" style="background: rgba(255,255,255,0.4); border: 1px solid var(--rc-border); width:10px;height:10px;"></span> Today
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                {{-- Days of Week Header --}}
                                <div class="d-grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr);">
                                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                                        <div class="fw-bold text-uppercase" style="color: var(--rc-text-muted); font-size: 0.7rem; letter-spacing: 0.5px;">{{ $dayName }}</div>
                                    @endforeach
                                </div>

                                {{-- Calendar Grid --}}
                                <div class="d-grid gap-1" style="grid-template-columns: repeat(7, 1fr);">
                                    {{-- Empty slots for previous month --}}
                                    @for($i = 0; $i < $startDayOfWeek; $i++)
                                        <div class="p-2"></div>
                                    @endfor

                                    {{-- Days of the month --}}
                                    @for($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $dateKey = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                            $loggedThisDay = $logsByDate[$dateKey] ?? 0;
                                            $isDone = $loggedThisDay > 0;
                                            $isToday = ($day == $todayDay);
                                            $isPast = ($day < $todayDay);

                                            // Determine styling
                                            if ($isDone) {
                                                $dayClass = 'text-white shadow-sm';
                                                $dayStyle = 'background: var(--rc-green); cursor:pointer; transition: all 0.2s;';
                                            } elseif ($isToday) {
                                                $dayClass = 'text-white shadow fw-bold';
                                                $dayStyle = 'background: rgba(255, 255, 255, 0.2); border: 1px solid var(--rc-border); cursor:pointer; transition: all 0.2s;';
                                            } else {
                                                $dayClass = '';
                                                $dayStyle = 'background: var(--rc-surface-2); color: var(--rc-text); cursor:pointer; transition: all 0.2s;';
                                            }
                                        @endphp
                                        <div class="position-relative d-flex justify-content-center py-1">
                                            <div class="d-flex flex-column align-items-center justify-content-center rounded-circle calendar-day-circle {{ $dayClass }}"
                                                 style="{{ $dayStyle }}"
                                                 @if($isDone)
                                                     title="{{ $loggedThisDay }}km logged"
                                                     data-bs-toggle="tooltip"
                                                     onclick="viewRunDetails('{{ $dateKey }}')"
                                                 @endif>
                                                <span style="font-size: 0.85rem;">{{ $day }}</span>
                                                @if($isDone)
                                                    <i class="fa-solid fa-check" style="font-size: 0.4rem; margin-top: -2px;"></i>
                                                @endif
                                            </div>
                                            @if($isDone)
                                                <span class="position-absolute bottom-0 start-50 translate-middle-x mb-0 rounded-circle"
                                                    style="background: #ffffff; width: 4px; height: 4px;"></span>
                                            @endif
                                        </div>
                                    @endfor
                                </div>

                                {{-- Monthly Summary --}}
                                @if($challenge)
                                    @php
                                        $monthTotal = 0;
                                        $daysCompleted = 0;
                                        for ($d = 1; $d <= $daysInMonth; $d++) {
                                            $dk = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $d);
                                            $dist = $logsByDate[$dk] ?? 0;
                                            $monthTotal += $dist;
                                            if ($dist > 0) $daysCompleted++;
                                        }
                                    @endphp
                                    <div class="row g-3 mt-4 pt-3" style="border-top: 1px solid var(--rc-border);">
                                        <div class="col-4 text-center">
                                            <div class="rounded-4 p-3 h-100" style="background: rgba(0, 210, 106, 0.1); border: 1px solid rgba(0, 210, 106, 0.2);">
                                                <div class="fw-bold fs-5" style="color: var(--rc-green);">{{ $daysCompleted }}</div>
                                                <div class="small" style="color: var(--rc-text-muted);">Days Active</div>
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="rounded-4 p-3 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border);">
                                                <div class="fw-bold fs-5" style="color: var(--rc-text);">{{ number_format($monthTotal, 1) }} km</div>
                                                <div class="small" style="color: var(--rc-text-muted);">This Month</div>
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="rounded-4 p-3 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border);">
                                                <div class="fw-bold fs-5" style="color: var(--rc-text);">{{ $progress }}%</div>
                                                <div class="small" style="color: var(--rc-text-muted);">Progress</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ═══ RIGHT: Challenge Sidebar ═══ --}}
                    <div class="col-lg-4 d-flex">
                        <div class="card border-0 shadow-sm rounded-4 w-100 overflow-hidden position-relative text-white"
                             style="background: linear-gradient(135deg, {{ $activeConfig['color'] }}dd, {{ $activeConfig['color'] }});">
                            <div class="position-absolute top-0 end-0 opacity-10 p-3">
                                <i class="fa-solid fa-person-running fa-8x"></i>
                            </div>
                            <div class="card-body p-4 position-relative d-flex flex-column">
                                @if($challenge)
                                    {{-- ═══ Active Challenge ═══ --}}
                                    <span class="badge bg-white bg-opacity-25 fw-bold mb-3 align-self-start">
                                        <i class="{{ $activeConfig['icon'] }} me-1"></i>{{ $activeConfig['label'] }} Challenge
                                    </span>

                                    <h3 class="fw-bold mb-1">{{ $activeConfig['description'] }}</h3>
                                    <p class="opacity-75 mb-4">
                                        {{ $daysLeft }} days remaining &bull; {{ $challenge->distance_logged }}/{{ $challenge->target_distance }} km
                                    </p>

                                    {{-- Progress Bar --}}
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between small opacity-75 mb-1">
                                            <span>Progress</span>
                                            <span>{{ $progress }}%</span>
                                        </div>
                                        <div class="progress" style="background-color: rgba(255, 255, 255, 0.3) !important; padding: 0; box-shadow: none; height: 10px; border-radius: 20px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="background-color: #ffffff !important; width: {{ $progress }}%; border-radius: 20px;"></div>
                                        </div>
                                    </div>

                                    {{-- Stats Row --}}
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="me-3">
                                            <div class="small opacity-75 text-uppercase fw-bold">Distance</div>
                                            <div class="fw-bold fs-5">{{ $challenge->target_distance }} km</div>
                                        </div>
                                        <div class="vr bg-white opacity-50 mx-3"></div>
                                        <div class="me-3">
                                            <div class="small opacity-75 text-uppercase fw-bold">Duration</div>
                                            <div class="fw-bold fs-5">{{ $challenge->duration_days }}d</div>
                                        </div>
                                        @if($isDaily)
                                        <div class="vr bg-white opacity-50 mx-3"></div>
                                        <div>
                                            <div class="small opacity-75 text-uppercase fw-bold">Daily</div>
                                            <div class="fw-bold fs-5">{{ $dailyTarget }} km</div>
                                        </div>
                                        @endif
                                    </div>

                                    @if($isDaily)
                                        <div class="bg-white rounded-4 p-3 mb-4 shadow-sm text-dark">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="fw-bold text-uppercase text-muted">Today</small>
                                                <small class="fw-bold text-dark">{{ $todayLogged }}/{{ $dailyTarget }} km</small>
                                            </div>
                                            <div class="progress bg-light" style="height: 6px; border-radius: 10px;">
                                                <div class="progress-bar {{ $todayLogged >= $dailyTarget ? 'bg-success' : 'bg-primary' }}"
                                                     style="width: {{ min(100, $dailyTarget > 0 ? round(($todayLogged / $dailyTarget) * 100) : 0) }}%; border-radius: 10px;"></div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ═══ Offline Sync Alert ═══ --}}
                                    <div id="offlineSyncAlert" class="alert alert-warning border-0 shadow-sm d-none mb-4" role="alert">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                                <strong><span id="pendingRunCount">0</span> run(s) waiting to sync</strong>
                                            </div>
                                            <button class="btn btn-sm btn-dark rounded-pill px-3 fw-bold" onclick="syncPendingRuns()" id="btnSyncRuns">
                                                Sync Now
                                            </button>
                                        </div>
                                    </div>

                                    {{-- GPS Tracking Module --}}
                                    <div class="mt-auto" id="gpsTrackingModule">
                                        {{-- State 1: Idle - Start Button --}}
                                        <div id="gpsIdle">
                                            <button type="button" class="btn bg-white w-100 fw-bold rounded-pill py-3 shadow-sm" style="color: {{ $activeConfig['color'] }}; font-size: 1.1rem; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='none'" onclick="startGpsTracking()">
                                                <i class="fa-solid fa-play me-2"></i>Start Run
                                            </button>
                                        </div>

                                        {{-- State 2: Tracking --}}
                                        <div id="gpsTracking" class="d-none">
                                            {{-- GPS Status --}}
                                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                                <span class="gps-pulse"></span>
                                                <small class="fw-bold text-uppercase opacity-75" id="gpsStatus">Acquiring GPS...</small>
                                            </div>

                                            {{-- Live Distance Display --}}
                                            <div class="text-center mb-4 d-flex justify-content-center">
                                                <div class="d-flex flex-column align-items-center justify-content-center position-relative" 
                                                     style="width: 170px; height: 170px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.8); background: rgba(0,0,0,0.1); box-shadow: 0 0 30px rgba(0,0,0,0.15);">
                                                    
                                                    <!-- Pulse Animation Rings -->
                                                    <div class="position-absolute w-100 h-100 rounded-circle" style="border: 2px solid rgba(255,255,255,0.4); animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;"></div>
                                                    <div class="position-absolute w-100 h-100 rounded-circle" style="border: 2px solid rgba(255,255,255,0.2); animation: pulse-ring 2.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; animation-delay: 0.5s;"></div>

                                                    <style>
                                                        @keyframes pulse-ring {
                                                            0% { transform: scale(1); opacity: 1; }
                                                            100% { transform: scale(1.3); opacity: 0; }
                                                        }
                                                    </style>

                                                    <!-- Number Display -->
                                                    <div class="fw-bold z-1" style="font-size: 2.2rem; line-height: 1.1; letter-spacing: -1px; text-shadow: 0 2px 4px rgba(0,0,0,0.2);" id="liveDistance">0 m</div>
                                                    <div class="text-uppercase fw-bold opacity-75 small z-1 mt-1" style="letter-spacing: 1px; font-size: 0.75rem;">distance</div>
                                                </div>
                                            </div>

                                            {{-- Run Progress Bar --}}
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between small opacity-75 mb-2">
                                                    <span class="fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Run Progress</span>
                                                    <span id="runProgressLabel" class="fw-bold">0%</span>
                                                </div>
                                                <div class="progress" style="background-color: rgba(255, 255, 255, 0.3) !important; padding: 0; box-shadow: none; height: 8px; border-radius: 20px;">
                                                    <div class="progress-bar" id="runProgressBar" role="progressbar"
                                                         style="background-color: #ffffff !important; width: 0%; border-radius: 20px; transition: width 0.5s ease;"></div>
                                                </div>
                                            </div>

                                            {{-- Live Stats (Time) --}}
                                            <div class="mb-4 px-4 text-center">
                                                <div class="small opacity-75 text-uppercase fw-bold mb-1">Duration Time</div>
                                                <div class="fw-bold fs-5 d-flex align-items-center justify-content-center">
                                                    <i class="fa-regular fa-clock me-2 opacity-50 small"></i>
                                                    <span id="elapsedTime">00:00:00</span>
                                                </div>
                                            </div>

                                            {{-- Action Buttons --}}
                                            <div class="d-flex flex-column gap-2">
                                                <button type="button" id="btnViewMap" class="btn btn-outline-light w-100 fw-bold rounded-pill py-3 d-none" onclick="enterFullScreenTracking()">
                                                    <i class="fa-solid fa-map-location-dot me-2"></i>View Map
                                                </button>
                                                <button type="button" class="btn w-100 fw-bold rounded-pill py-3 text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'" onclick="finishGpsTracking()">
                                                    <i class="fa-solid fa-flag-checkered me-2"></i>Finish Run
                                                </button>
                                            </div>
                                        </div>{{-- END State 2: gpsTracking --}}

                                        {{-- State 3: Submitting --}}
                                        <div id="gpsSubmitting" class="d-none text-center">
                                            <div class="spinner-border text-white mb-3" role="status"></div>
                                            <p class="fw-bold mb-0">Saving your run...</p>
                                        </div>

                                        {{-- State 4: No Distance Tracked --}}
                                        <div id="gpsNoDistance" class="d-none">
                                            <div class="bg-white rounded-4 p-4 text-center mb-3 text-dark">
                                                <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                                                <h6 class="fw-bold mb-2 text-dark">No Distance Tracked</h6>
                                                <p class="small text-muted mb-0">No significant distance was recorded during this run. Make sure your GPS is enabled and you're moving.</p>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-light w-50 fw-bold rounded-pill py-2" onclick="cancelNoDistanceRun()">
                                                    Cancel Run
                                                </button>
                                                <button type="button" class="btn btn-light text-dark w-50 fw-bold rounded-pill py-2" onclick="retryGpsTracking()">
                                                    <i class="fa-solid fa-redo me-1"></i>Try Again
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Hidden form for submission --}}
                                        <form method="POST" action="{{ route('challenges.log') }}" id="gpsLogForm" class="d-none">
                                            @csrf
                                            <input type="hidden" name="distance_km" id="gpsDistanceInput">
                                            <input type="hidden" name="duration_seconds" id="gpsDurationInput">
                                            <input type="hidden" name="route_data" id="gpsRouteDataInput">
                                        </form>
                                    </div>{{-- END gpsTrackingModule --}}

                                @else
                                    {{-- ═══ No Active Challenge ═══ --}}
                                    <span class="badge bg-white bg-opacity-25 fw-bold mb-3 align-self-start">
                                        <i class="{{ $activeConfig['icon'] }} me-1"></i>{{ $activeConfig['label'] }}
                                    </span>

                                    <h3 class="fw-bold mb-1">{{ $activeConfig['description'] }}</h3>
                                    <p class="opacity-75 mb-4">Ready to take on the {{ $activeConfig['label'] }} challenge?</p>

                                    <div class="d-flex align-items-center mb-4">
                                        <div class="me-3">
                                            <div class="small opacity-75 text-uppercase fw-bold">Distance</div>
                                            <div class="fw-bold fs-5">{{ $activeConfig['target_distance'] }} km</div>
                                        </div>
                                        <div class="vr bg-white opacity-50 mx-3"></div>
                                        <div>
                                            <div class="small opacity-75 text-uppercase fw-bold">Duration</div>
                                            <div class="fw-bold fs-5">{{ $activeConfig['duration_days'] }}d</div>
                                        </div>
                                    </div>

                                    {{-- Level ladder preview --}}
                                    <div class="mb-4 flex-grow-1">
                                        @foreach($challengeLevels as $key => $lvl)
                                            @php
                                                $lvlIdx = array_search($key, $levelOrder);
                                                $isCur = ($key === $currentFitness);
                                                $isPassed = ($lvlIdx < $currentIndex);
                                                $isLocked = ($lvlIdx > $currentIndex);
                                            @endphp
                                            <div class="d-flex align-items-center gap-2 py-1 {{ $isCur ? 'opacity-100' : 'opacity-50' }}">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                     style="width:28px; height:28px; background: {{ $isCur ? '#fff' : 'rgba(255,255,255,0.2)' }}; color: {{ $isCur ? $activeConfig['color'] : '#fff' }}; font-size:0.7rem;">
                                                    @if($isPassed)
                                                        <i class="fa-solid fa-check d-none d-md-block"></i>
                                                    @elseif($isLocked)
                                                        <i class="fa-solid fa-lock"></i>
                                                    @else
                                                        <i class="{{ $lvl['icon'] }}"></i>
                                                    @endif
                                                </div>
                                                <span class="small fw-bold">{{ $lvl['label'] }}</span>
                                                @if($isCur)
                                                    <span class="badge bg-white bg-opacity-25 ms-auto" style="font-size:0.6rem;">YOU</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <form method="POST" action="{{ route('challenges.start') }}" class="mt-auto">
                                        @csrf
                                        <input type="hidden" name="level" value="{{ $currentFitness }}">
                                        <button type="submit" class="btn btn-light text-dark w-100 fw-bold rounded-pill py-3">
                                            Start Challenge <i class="fa-solid fa-arrow-right ms-2"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Tab -->
            <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
                @if(isset($events) && $events->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-3 text-white opacity-50"><i class="fa-solid fa-calendar-xmark fa-4x"></i></div>
                        <h5 class="fw-bold text-white opacity-75">No upcoming events found.</h5>
                        <p class="text-white opacity-50 small">Check back later for new running events!</p>
                    </div>
                @elseif(isset($events))
                    {{-- Offline Message --}}
                    <div id="offlineEventsMessage" class="d-none text-center py-5">
                        <div class="mb-3 text-white opacity-50"><i class="fa-solid fa-wifi fa-4x text-danger"></i></div>
                        <h5 class="fw-bold text-white opacity-75">No Internet Connection</h5>
                        <p class="text-white opacity-50 small">Connect to the internet to browse and register for events.</p>
                    </div>

                    <div class="row g-4 mt-2" id="eventsListContainer">
                        @foreach($events as $event)
                            @php
                                $isRegistered = in_array($event->id, $registeredEventIds ?? []);
                                $registeredCount = (int) ($event->registered_count ?? 0);
                                $slotsLeft = (int) $event->slots - $registeredCount;
                                $isFull = $slotsLeft <= 0;
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                {{-- ══ PROFESSIONAL EVENT CARD ══ --}}
                                <div class="rc-event-card rounded-4 h-100 d-flex flex-column {{ $isRegistered ? 'rc-event-card--registered' : '' }}" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">

                                    {{-- Gradient Header Banner --}}
                                    <div style="background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%); padding: 18px 20px 14px; position: relative;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 75%;">{{ $event->name }}</h5>
                                            <span class="badge fw-bold" style="background: rgba(255,255,255,0.2); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                                {{ $event->formatted_distance }}
                                            </span>
                                        </div>

                                        {{-- Relevance match badge --}}
                                        @if(isset($event->relevance_score) && $event->relevance_score > 0)
                                            <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 0.72rem; padding: 4px 10px; border-radius: 20px;">
                                                <i class="fa-solid fa-star" style="font-size: 0.65rem;"></i> {{ $event->relevance_score }}% Match
                                            </span>
                                        @endif

                                        {{-- Status chip --}}
                                        @if($isRegistered)
                                            <span class="position-absolute" style="top: 12px; right: 12px; background: rgba(255,255,255,0.15); color: #a8ffcc; font-size: 0.65rem; font-weight: 700; padding: 3px 9px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.25); letter-spacing: 0.4px;">
                                                ✓ ENROLLED
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Body --}}
                                    <div class="flex-grow-1 px-4 pt-3 pb-2">
                                        {{-- Organiser --}}
                                        <p class="mb-3" style="color: rgba(255,255,255,0.7); font-size: 0.82rem;">{{ Str::limit($event->description, 80) }}</p>

                                        {{-- Info rows --}}
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                            <div class="d-flex align-items-start gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                                <span style="color: rgba(255,255,255,0.88); line-height: 1.4;">{{ $event->location ?? 'Location TBD' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                                <span style="color: rgba(255,255,255,0.88);">{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'Date TBD' }}@if($event->event_time) &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($event->event_time)->format('h:i A') }}@endif</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0;"><i class="fa-solid fa-tag"></i></span>
                                                <span style="color: rgba(255,255,255,0.88);">{{ $event->registration_fee > 0 ? '₱' . number_format($event->registration_fee, 2) : 'Free' }}</span>
                                            </div>
                                        </div>

                                        {{-- Divider --}}
                                        <div style="border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0;"></div>

                                        {{-- Footer row: difficulty + slots --}}
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
                                            <span style="color: rgba(255,255,255,0.55); font-size: 0.76rem;">
                                                <i class="fa-solid fa-users me-1"></i>{{ $registeredCount }} / {{ $event->slots }} slots
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="px-4 pb-4 pt-2 d-flex gap-2">
                                        <button class="btn fw-semibold flex-fill" style="background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.75); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'" onclick='viewEventDetails(@json($event))'>
                                            <i class="fa-regular fa-eye me-1"></i> Details
                                        </button>
                                        @if($isRegistered)
                                            <button class="btn fw-bold flex-fill event-track-btn" style="background: linear-gradient(135deg,#0e6e3e,#1aad6e); color:#fff; border:none; border-radius:50px; font-size:0.83rem; padding:9px 0;"
                                                data-id="{{ $event->id }}"
                                                data-route="{{ $event->route_data }}"
                                                data-name="{{ addslashes($event->name) }}"
                                                onclick="startEventTracking({{ $event->id }}, '{{ addslashes($event->name) }}')">
                                                <i class="fa-solid fa-satellite-dish me-1"></i> Live Track
                                            </button>
                                        @elseif($isFull)
                                            <button class="btn fw-semibold flex-fill" style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.07); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" disabled>
                                                <i class="fa-solid fa-ban me-1"></i> Full
                                            </button>
                                        @else
                                            <button class="btn fw-bold flex-fill" style="background: linear-gradient(135deg,#0e6e3e,#1aad6e); color:#fff; border:none; border-radius:50px; font-size:0.83rem; padding:9px 0;"
                                                onclick="openRegistration({{ $event->id }}, '{{ addslashes($event->name) }}', {{ (float)$event->registration_fee }})">
                                                <i class="fa-solid fa-clipboard-check me-1"></i> Register
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Footer strip --}}
                                    @if($isRegistered)
                                        <div class="text-center py-2" style="background: rgba(26,173,110,0.12); border-top: 1px solid rgba(26,173,110,0.2);">
                                            <small class="fw-bold text-success" style="font-size: 0.68rem; letter-spacing: 0.5px;"><i class="fa-solid fa-circle-check me-1"></i> YOU'RE IN! See you there!</small>
                                        </div>
                                    @else
                                        <div class="text-center py-2" style="background: rgba(255,255,255,0.04); border-top: 1px solid rgba(255,255,255,0.07);">
                                            <small class="fw-bold text-uppercase" style="color: rgba(255,255,255,0.6); font-size: 0.65rem; letter-spacing: 0.8px;">Register by {{ \Carbon\Carbon::parse($event->registration_end)->format('M d, Y') }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- My Events Tab -->
            <div class="tab-pane fade" id="user-my-events" role="tabpanel" aria-labelledby="my-events-tab">
                @if(isset($myRegistrations) && $myRegistrations->isEmpty())
                    <div class="text-center py-5">
                         <div class="mb-3 text-white opacity-25"><i class="fa-solid fa-shoe-prints fa-4x"></i></div>
                         <h5 class="fw-bold text-white opacity-75">No participated events yet.</h5>
                         <p class="text-white opacity-50 small">Register for an event to see it here!</p>
                    </div>
                @elseif(isset($myRegistrations))
                    {{-- Offline Message for My Events --}}
                    <div id="offlineMyEventsMessage" class="d-none text-center py-5">
                        <div class="mb-3 text-white opacity-50"><i class="fa-solid fa-wifi fa-4x text-danger"></i></div>
                        <h5 class="fw-bold text-white opacity-75">No Internet Connection</h5>
                        <p class="text-white opacity-50 small">Connect to the internet to view your registered events.</p>
                    </div>
                    <div class="row g-4 mt-2" id="myEventsListContainer">
                        @foreach($myRegistrations as $reg)
                            @php
                                $event = $reg->event;
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                {{-- ══ PROFESSIONAL MY-EVENT CARD ══ --}}
                                <div class="rc-event-card rounded-4 h-100 d-flex flex-column" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">

                                    {{-- Gradient Header Banner --}}
                                    <div style="background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%); padding: 18px 20px 14px; position: relative;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 75%;">{{ $event->name }}</h5>
                                            <span class="badge fw-bold" style="background: rgba(255,255,255,0.2); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                                {{ $event->formatted_distance }}
                                            </span>
                                        </div>
                                        {{-- Enrolled chip --}}
                                        <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); color: #a8ffcc; font-size: 0.68rem; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">
                                            <i class="fa-solid fa-ticket" style="font-size: 0.6rem;"></i> Registered
                                        </span>
                                    </div>

                                    {{-- Body --}}
                                    <div class="flex-grow-1 px-4 pt-3 pb-2">
                                        <p class="mb-3 fw-semibold" style="color: rgba(255,255,255,0.45); font-size: 0.78rem; letter-spacing: 0.2px;">{{ Str::limit($event->description, 80) }}</p>

                                        <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                            <div class="d-flex align-items-start gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                                <span style="color: rgba(255,255,255,0.65); line-height: 1.4;">{{ $event->location ?? 'Location TBD' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                                <span style="color: rgba(255,255,255,0.65);">{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'Date TBD' }}</span>
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
                                            @php $evStatus = $event->status; @endphp
                                            <span class="fw-bold" style="font-size: 0.72rem; color: {{ $evStatus === 'started' ? '#1aad6e' : ($evStatus === 'completed' ? 'rgba(255,255,255,0.3)' : 'rgba(255,193,7,0.85)') }};">
                                                @if($evStatus === 'started') <i class="fa-solid fa-circle-dot me-1"></i> Live
                                                @elseif($evStatus === 'completed') <i class="fa-solid fa-flag-checkered me-1"></i> Finished
                                                @else <i class="fa-regular fa-clock me-1"></i> Upcoming @endif
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="px-4 pb-4 pt-2 d-flex gap-2">
                                        <button class="btn fw-semibold flex-fill" style="background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.75); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'" onclick="viewEventDetails({{ json_encode($event) }})">
                                            <i class="fa-regular fa-eye me-1"></i> Details
                                        </button>
                                        @if($event->status === 'started')
                                            <button class="btn fw-bold flex-fill event-track-btn" style="background: linear-gradient(135deg,#0e6e3e,#1aad6e); color:#fff; border:none; border-radius:50px; font-size:0.83rem; padding:9px 0;"
                                                data-id="{{ $event->id }}"
                                                data-route="{{ $event->route_data }}"
                                                data-name="{{ addslashes($event->name) }}"
                                                onclick="startEventTracking({{ $event->id }}, '{{ addslashes($event->name) }}')">
                                                <i class="fa-solid fa-satellite-dish me-1"></i> Join Now
                                            </button>
                                        @elseif($event->status === 'completed')
                                            <button class="btn fw-semibold flex-fill" style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.07); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" disabled>
                                                <i class="fa-solid fa-flag-checkered me-1"></i> Completed
                                            </button>
                                        @else
                                            <button class="btn fw-semibold flex-fill" style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.07); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" disabled>
                                                <i class="fa-regular fa-clock me-1"></i> Waiting
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Footer strip --}}
                                    <div class="text-center py-2" style="background: rgba(255,255,255,0.04); border-top: 1px solid rgba(255,255,255,0.07);">
                                        <small class="fw-bold text-uppercase" style="color: rgba(255,255,255,0.3); font-size: 0.65rem; letter-spacing: 0.8px;">Registered on {{ $reg->created_at->format('M d, Y') }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="user-history" role="tabpanel" aria-labelledby="history-tab">
                {{-- ═══ EVENT HISTORY — Finished Events ═══ --}}
                <div class="mt-2 pt-2">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 40px; height: 40px; background: rgba(255,193,7,0.12); border: 1px solid rgba(255,193,7,0.2);">
                            <i class="fa-solid fa-clock-rotate-left" style="color: #ffc107; font-size: 1rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color: var(--rc-text); font-size: 1.05rem;">Event History</h5>
                            <small style="color: var(--rc-text-muted); font-size: 0.75rem;">Your completed running events</small>
                        </div>
                        @if(isset($finishedRegistrations) && $finishedRegistrations->count() > 0)
                            <span class="badge ms-auto fw-bold" style="background: rgba(255,193,7,0.15); color: #ffc107; border: 1px solid rgba(255,193,7,0.25); border-radius: 20px; padding: 5px 12px; font-size: 0.72rem;">
                                {{ $finishedRegistrations->count() }} {{ Str::plural('event', $finishedRegistrations->count()) }}
                            </span>
                        @endif
                    </div>

                    @if(!isset($finishedRegistrations) || $finishedRegistrations->isEmpty())
                        <div class="text-center py-5 rounded-4" style="background: var(--rc-surface-2); border: 1px dashed rgba(255,255,255,0.1);">
                            <div class="mb-3 opacity-25"><i class="fa-solid fa-trophy fa-3x" style="color: var(--rc-text-muted);"></i></div>
                            <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No finished events yet</h6>
                            <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">Completed events will appear here after they finish.</p>
                        </div>
                    @else
                        <div class="row g-4">
                            @foreach($finishedRegistrations as $fReg)
                                @php
                                    $fEvent = $fReg->event;
                                    $trackedDistance = $fReg->current_distance ?? 0;
                                    $eventDistance = floatval(preg_replace('/[^0-9.]/', '', $fEvent->distance ?? '0'));
                                    $completionPct = $eventDistance > 0 ? min(100, round(($trackedDistance / $eventDistance) * 100)) : 0;
                                @endphp
                                <div class="col-md-6 col-lg-4">
                                    <div class="rc-event-card rounded-4 h-100 d-flex flex-column" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s, opacity .18s; opacity: 0.92;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)';this.style.opacity='1'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';this.style.opacity='0.92'">

                                        {{-- Muted Gradient Header for finished events --}}
                                        <div style="background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%); padding: 18px 20px 14px; position: relative;">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 70%;">{{ $fEvent->name }}</h5>
                                                <span class="badge fw-bold" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                                    {{ $fEvent->formatted_distance }}
                                                </span>
                                            </div>
                                            {{-- Finished chip --}}
                                            <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,193,7,0.2); color: #ffd866; font-size: 0.68rem; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,193,7,0.3);">
                                                <i class="fa-solid fa-flag-checkered" style="font-size: 0.6rem;"></i> Finished
                                            </span>
                                            {{-- Trophy watermark --}}
                                            <div class="position-absolute" style="bottom: 8px; right: 14px; opacity: 0.08;">
                                                <i class="fa-solid fa-trophy fa-3x text-white"></i>
                                            </div>
                                        </div>

                                        {{-- Body --}}
                                        <div class="flex-grow-1 px-4 pt-3 pb-2">
                                            <p class="mb-3 fw-semibold" style="color: rgba(255,255,255,0.4); font-size: 0.78rem; letter-spacing: 0.2px;">{{ Str::limit($fEvent->description, 80) }}</p>

                                            <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                                <div class="d-flex align-items-start gap-3">
                                                    <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                                    <span style="color: rgba(255,255,255,0.55); line-height: 1.4;">{{ $fEvent->location ?? 'Location TBD' }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                                    <span style="color: rgba(255,255,255,0.55);">{{ $fEvent->event_date ? \Carbon\Carbon::parse($fEvent->event_date)->format('M d, Y') : 'Date TBD' }}</span>
                                                </div>
                                                @if($trackedDistance > 0)
                                                <div class="d-flex align-items-center gap-3">
                                                    <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-solid fa-route"></i></span>
                                                    <span style="color: rgba(255,255,255,0.55);">{{ number_format($trackedDistance, 2) }} km tracked</span>
                                                </div>
                                                @endif
                                            </div>

                                            @if($trackedDistance > 0)
                                                <div class="mt-3 mb-2">
                                                    <div class="d-flex justify-content-between small mb-1">
                                                        <span style="color: rgba(255,255,255,0.35); font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">Completion</span>
                                                        <span style="color: rgba(255,193,7,0.8); font-size: 0.7rem; font-weight: 700;">{{ $completionPct }}%</span>
                                                    </div>
                                                    <div class="progress" style="height: 5px; background: rgba(255,255,255,0.08) !important; border-radius: 20px;">
                                                        <div class="progress-bar" style="width: {{ $completionPct }}%; background: linear-gradient(90deg, #ffc107, #ff9800) !important; border-radius: 20px;"></div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0;"></div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge fw-semibold" style="
                                                    font-size: 0.72rem; padding: 5px 12px; border-radius: 20px;
                                                    @switch($fEvent->difficulty)
                                                        @case('Beginner') background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.3); @break
                                                        @case('Improving') background: rgba(13,202,240,0.12); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3); @break
                                                        @case('Intermediate') background: rgba(255,193,7,0.12); color: #ffc107; border: 1px solid rgba(255,193,7,0.3); @break
                                                        @default background: rgba(108,117,125,0.15); color: #adb5bd; border: 1px solid rgba(108,117,125,0.3);
                                                    @endswitch
                                                ">
                                                    {{ $fEvent->difficulty }}
                                                </span>
                                                <span class="fw-bold" style="font-size: 0.72rem; color: rgba(255,193,7,0.7);">
                                                    <i class="fa-solid fa-flag-checkered me-1"></i> Completed
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Action Button --}}
                                        <div class="px-4 pb-4 pt-2">
                                            <button class="btn fw-semibold w-100" style="background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; font-size: 0.83rem; padding: 9px 0;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'" onclick="viewEventDetails({{ json_encode($fEvent) }})">
                                                <i class="fa-regular fa-eye me-1"></i> View Details
                                            </button>
                                        </div>

                                        {{-- Footer strip --}}
                                        <div class="text-center py-2" style="background: rgba(255,193,7,0.05); border-top: 1px solid rgba(255,193,7,0.1);">
                                            <small class="fw-bold text-uppercase" style="color: rgba(255,193,7,0.4); font-size: 0.65rem; letter-spacing: 0.8px;">
                                                <i class="fa-solid fa-trophy me-1" style="font-size: 0.55rem;"></i>
                                                Event completed {{ $fEvent->event_date ? \Carbon\Carbon::parse($fEvent->event_date)->diffForHumans() : '' }}
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

        {{-- ═══ Run Details Modal ═══ --}}
        <div class="modal fade" id="runDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <div>
                            <h5 class="modal-title fw-bold" id="runDetailsDate">Run Details</h5>
                            <small class="opacity-75" id="runDetailsSubtitle">Loading...</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        {{-- Stats Only --}}
                        <div class="row g-4 justify-content-center">
                            <div class="col-6">
                                <div class="p-3 rounded-4 bg-light text-center">
                                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Distance</small>
                                    <div class="fw-bold fs-3 text-dark mb-0" id="runDetailsDistance">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-4 bg-light text-center">
                                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Duration</small>
                                    <div class="fw-bold fs-3 text-dark mb-0" id="runDetailsDuration">--:--</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Forfeit Modal ═══ --}}
        <div class="modal fade" id="forfeitConfirmModal" tabindex="-1" style="z-index: 10000;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                    <div class="modal-header border-0 bg-danger text-white p-4">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Forfeit Run
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <p class="mb-0">Are you sure you want to quit this current run? Your progress for this session will not be saved.</p>
                    </div>
                    <div class="modal-footer border-0 bg-light justify-content-center">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" onclick="quitAndForfeitEvent()">
                            Yes, Forfeit Run
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Full Screen Map Container (Hidden by Default) ═══ --}}
        <div id="runnerFullScreenMap" class="d-none"></div>

        {{-- ═══ Full Screen Overlays ═══ --}}
        <div id="runnerStatsOverlay" class="d-none flex-column justify-content-between p-3 p-md-4"
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; pointer-events: none;">
            
            {{-- Top Bar (Progress) --}}
            <div class="w-100 mt-2" style="pointer-events: auto;">
                <div class="text-white p-3 rounded-4 shadow-lg mx-auto" style="max-width: 600px; backdrop-filter: blur(12px); background: rgba(15,23,42,0.85); border: 1px solid rgba(255,255,255,0.1);">
                     <div class="d-flex justify-content-between align-items-center mb-2">
                         <div class="d-flex align-items-center gap-2">
                             <span class="gps-pulse"></span>
                             <small class="fw-bold text-uppercase" style="color: #22c55e; letter-spacing: 0.5px; font-size: 0.75rem;" id="fsGpsStatus">Tracking Live</small>
                         </div>
                         <div class="d-flex align-items-center gap-2">
                             <div id="fsOfflineBtnContainer"></div>
                             <span id="fsEventName" class="badge fw-bold d-none" style="background: rgba(26,173,110,0.2); color: #4ade80; border: 1px solid rgba(74,222,128,0.3); font-size: 0.7rem; padding: 4px 10px;"></span>
                         </div>
                     </div>
                     <div class="d-flex justify-content-between text-center mt-2">
                         <div class="flex-fill border-end border-white border-opacity-10 border-1 text-center">
                             <small class="text-uppercase fw-bold" style="color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 0.5px;">Time</small>
                             <div class="fw-bold fs-5" id="fsElapsedTime" style="font-variant-numeric: tabular-nums;">00:00:00</div>
                         </div>
                         <div class="flex-fill text-center">
                             <small class="text-uppercase fw-bold" style="color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 0.5px;">Distance</small>
                             <div class="fw-bold fs-4" id="fsLiveDistance" style="color: #4ade80; font-variant-numeric: tabular-nums;">0.00 <span class="fs-6" style="color: rgba(255,255,255,0.4);">km</span></div>
                         </div>
                     </div>
                </div>
            </div>

            {{-- Bottom Bar (Actions) --}}
            <div class="w-100 mb-4 pb-4 mt-auto d-flex flex-column align-items-center gap-2" style="pointer-events: auto; max-width: 400px; margin: 0 auto;">
                {{-- Finish Event Run button (only shown during event tracking) --}}
                <button type="button" id="fsFinishEventBtn" class="btn w-100 fw-bold rounded-pill py-3 shadow-lg d-none" style="background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; border: none; font-size: 1rem; letter-spacing: 0.3px;" onclick="finishGpsTracking()">
                    <i class="fa-solid fa-flag-checkered me-2"></i> Finish Event Run
                </button>
                <button type="button" id="fsEmergencyBtn" class="btn w-100 fw-bold rounded-pill py-3 shadow-lg d-none mt-2 text-white" style="background: linear-gradient(135deg, #f97316, #b91c1c); border: none; font-size: 1.05rem;" onclick="reportEmergency()">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Emergency SOS
                </button>
                <button type="button" class="btn w-100 rounded-pill fw-bold px-4 py-3 shadow-lg" style="backdrop-filter: blur(12px); background: rgba(15,23,42,0.85); color: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.15);" onclick="exitFullScreenTracking()">
                    <i class="fa-solid fa-compress me-2"></i> Minimize / Close Map
                </button>
            </div>
        </div>

        {{-- ═══ GPS / Location Not Available Modal ═══ --}}
        <div class="modal fade" id="gpsUnavailableModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg" style="background-color: var(--rc-surface); border: 1px solid var(--rc-border) !important;">
                    <div class="text-white text-center py-5 px-4 position-relative" style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%);">
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at top right, rgba(26,173,110,0.15), transparent 60%);"></div>
                        <div class="mb-3 position-relative z-1">
                            <i class="fa-solid fa-location-crosshairs fa-4x mb-2 text-warning" style="filter: drop-shadow(0 0 15px rgba(255,193,7,0.4));"></i>
                        </div>
                        <h4 class="fw-bold mb-2 position-relative z-1 text-white">Location Services Required</h4>
                        <p class="opacity-75 mb-0 position-relative z-1 small px-2">GPS tracking cannot start. This usually happens if location access is denied, or if your connection is not secure.</p>
                    </div>
                    <div class="p-4" style="background-color: var(--rc-surface);">
                        <div class="text-center mb-4">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <div class="d-flex align-items-center gap-3 p-3 rounded-4 w-100" style="background-color: var(--rc-surface-2); border: 1px solid var(--rc-border);">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px; height:40px; background: rgba(26,173,110,0.15); color: #1aad6e;">
                                        <i class="fa-solid fa-lock fw-bold"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="fw-bold small text-white">Secure Connection (HTTPS)</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Browsers require GPS to be on a secure <code>https://</code> or <code>localhost</code> connection.</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3 p-3 rounded-4 w-100 mt-2" style="background-color: var(--rc-surface-2); border: 1px solid var(--rc-border);">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px; height:40px; background: rgba(13,202,240,0.15); color: #0dcaf0;">
                                        <i class="fa-solid fa-location-dot fw-bold"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="fw-bold small text-white">Allow Location Access</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Check your browser/phone settings and tap "Allow" when prompted for location.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn w-100 rounded-pill py-2 fw-bold text-dark" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #1aad6e 0%, #0e6e3e 100%); color: white !important; border: none;">
                            Understood & Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Challenge Completed Modal ═══ --}}
        <div class="modal fade" id="challengeCompleteModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="text-white text-center py-5 px-4" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        <i class="fa-solid fa-trophy fa-4x mb-3"></i>
                        <h3 class="fw-bold mb-2">Challenge Complete! 🎉</h3>
                        <p class="opacity-75 mb-0">
                            You crushed the
                            <strong>{{ isset($lastChallenge) && $lastChallenge ? ($challengeLevels[$lastChallenge->level]['label'] ?? '') : '' }}</strong>
                            challenge!
                        </p>
                    </div>
                    <div class="p-4">
                        @php
                            $nextLevelKey = isset($lastChallenge) && $lastChallenge ? ($challengeLevels[$lastChallenge->level]['on_success'] ?? null) : null;
                            $nextLevel = $nextLevelKey ? ($challengeLevels[$nextLevelKey] ?? null) : null;
                        @endphp

                        @if($nextLevel && $nextLevelKey !== ($lastChallenge->level ?? ''))
                            <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-primary bg-opacity-10 border border-primary mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:48px; height:48px; background: {{ $nextLevel['color'] }}; color: white;">
                                    <i class="{{ $nextLevel['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-primary">Next: {{ $nextLevel['label'] }}</div>
                                    <small class="text-muted">{{ $nextLevel['description'] }}</small>
                                </div>
                            </div>
                        @endif

                        <p class="text-muted text-center mb-4">Would you like to advance or redo this challenge?</p>

                        <div class="d-flex gap-3">
                            <form method="POST" action="{{ route('challenges.redo') }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill py-3 fw-bold">
                                    <i class="fa-solid fa-rotate-left me-2"></i>Redo
                                </button>
                            </form>
                            <form method="POST" action="{{ route('challenges.advance') }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">
                                    <i class="fa-solid fa-arrow-up me-2"></i>Advance
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Challenge Failed Modal ═══ --}}
        <div class="modal fade" id="challengeFailedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="text-white text-center py-5 px-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <i class="fa-solid fa-heart-crack fa-4x mb-3"></i>
                        <h3 class="fw-bold mb-2">Challenge Expired</h3>
                        <p class="opacity-75 mb-0">
                            The deadline has passed for the
                            <strong>{{ isset($lastChallenge) && $lastChallenge ? ($challengeLevels[$lastChallenge->level]['label'] ?? '') : '' }}</strong>
                            challenge.
                        </p>
                    </div>
                    <div class="p-4">
                        @php
                            $failLevelKey = isset($lastChallenge) && $lastChallenge ? ($challengeLevels[$lastChallenge->level]['on_failure'] ?? null) : null;
                            $failLevel = $failLevelKey ? ($challengeLevels[$failLevelKey] ?? null) : null;
                        @endphp

                        @if($failLevel)
                            <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-warning bg-opacity-10 border border-warning mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:48px; height:48px; background: {{ $failLevel['color'] }}; color: white;">
                                    <i class="{{ $failLevel['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-warning">Level: {{ $failLevel['label'] }}</div>
                                    <small class="text-muted">
                                        {{ $failLevelKey === ($lastChallenge->level ?? '') ? 'You stay at this level.' : 'You have been moved down.' }}
                                    </small>
                                </div>
                            </div>
                        @endif

                        <p class="text-muted text-center mb-4">Don't give up! Start a new challenge and try again.</p>

                        <form method="POST" action="{{ route('challenges.redo') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">
                                <i class="fa-solid fa-play me-2"></i>Start New Challenge
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    @endif

    {{-- ═══ Start Event Confirmation Modal ═══ --}}
    <style>
        .event-confirm-footer {
            display: flex !important;
            gap: 10px;
            align-items: stretch;
            flex-wrap: nowrap !important;
            justify-content: space-between;
        }
        .event-confirm-footer > .btn,
        .event-confirm-footer > form {
            flex: 1 1 0;
            margin: 0;
            min-width: 0;
        }
        .event-confirm-footer > .btn {
            width: 100%;
        }
        .event-confirm-footer > form .btn {
            width: 100%;
        }
        @media (max-width: 767.98px) {
            #startEventConfirmModal .event-confirm-footer {
                flex-direction: column-reverse;
            }
            #startEventConfirmModal .event-confirm-footer > .btn,
            #startEventConfirmModal .event-confirm-footer > form {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
    <div class="modal fade" id="startEventConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to <strong>start</strong> the event "<strong id="startEventModalName"></strong>"? Runners will be able to join live tracking.</p>
                </div>
                <div class="modal-footer border-0 pt-0 event-confirm-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="startEventModalForm" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-play me-1"></i>Start Event
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ End Event Confirmation Modal ═══ --}}
    <div class="modal fade" id="endEventConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to <strong>stop</strong> the event "<strong id="endEventModalName"></strong>"? All active runners' tracking will be stopped.</p>
                </div>
                <div class="modal-footer border-0 pt-0 event-confirm-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="endEventModalForm" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-stop me-1"></i>End Event
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-show challenge modals + GPS Tracking Module --}}
    
    {{-- Floating Active Run Button (Fixed bottom right) --}}
    <div id="floatingActiveRunBtn" class="position-fixed d-none" style="bottom: 90px; right: 20px; z-index: 9990; pointer-events: none;">
        <button type="button" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center pt-1 position-relative" 
                style="width: 70px; height: 70px; pointer-events: auto; animation: floatingBounce 2s infinite;"
                onclick="enterFullScreenTracking()" title="Return to Live Map">
            <i class="fa-solid fa-map-location-dot fs-3 text-white"></i>
            <span id="floatingElapsedTime" class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger border border-light shadow-sm" style="font-size: 0.7rem;">
                00:00:00
            </span>
        </button>
    </div>

    @push('scripts')
        {{-- GPS Pulse CSS --}}
        <style>
            @keyframes floatingBounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
            .gps-pulse {
                width: 10px;
                height: 10px;
                background: #22c55e;
                border-radius: 50%;
                display: inline-block;
                position: relative;
                animation: gpsPulse 1.5s ease-in-out infinite;
            }
            .gps-pulse::before {
                content: '';
                position: absolute;
                top: -4px;
                left: -4px;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                border: 2px solid #22c55e;
                animation: gpsPulseRing 1.5s ease-in-out infinite;
            }
            @keyframes gpsPulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.2); opacity: 0.8; }
            }
            @keyframes gpsPulseRing {
                0% { transform: scale(1); opacity: 0.6; }
                100% { transform: scale(1.8); opacity: 0; }
            }
            #liveDistance {
                font-variant-numeric: tabular-nums;
                text-shadow: 0 2px 20px rgba(0,0,0,0.15);
            }
        </style>

        <script>
            // ═══ Global Data ═══
            const challengeLogs = @json($allLogs ?? []);
            // Map removed as per request

            // ═══ View Run Details ═══
            function viewRunDetails(date) {
                // Find logs for this date
                // Note: logs might be mixed timezone, but date string comparison should catch 'YYYY-MM-DD'
                const logs = challengeLogs.filter(l => l.logged_date.substring(0, 10) === date);

                if (logs.length === 0) return;

                // Use the main log (largest distance or first one)
                logs.sort((a, b) => b.distance_km - a.distance_km);
                const log = logs[0];

                // Update Modal Content
                const d = new Date(date);
                document.getElementById('runDetailsDate').textContent = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                document.getElementById('runDetailsSubtitle').textContent = 'Daily Challenge Run';

                // Stats
                document.getElementById('runDetailsDistance').textContent = formatDistance(log.distance_km);

                // Duration
                if (log.duration_seconds) {
                    const h = Math.floor(log.duration_seconds / 3600);
                    const m = Math.floor((log.duration_seconds % 3600) / 60);
                    const s = log.duration_seconds % 60;
                    document.getElementById('runDetailsDuration').textContent =
                        (h > 0 ? h + 'h ' : '') + m + 'm ' + s + 's';
                } else {
                    document.getElementById('runDetailsDuration').textContent = 'N/A';
                }
                // Location Tracking Only (No Pace)
                // Show Modal
                const modalEl = document.getElementById('runDetailsModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }

            // ═══ GPS Tracking Module ═══
            let gpsWatchId = null;
            let gpsCoords = [];
            let totalDistanceKm = 0;
            let trackingStartTime = null;
            let timerInterval = null;
            let activeEventId = null;
            let lastKnownLocation = null;
            let liveBroadcastInterval = null;
            let challengeTargetKm = {{ $challenge ? $challenge->target_distance : 0 }};
            let challengeDistanceLogged = {{ $challenge ? $challenge->distance_logged : 0 }};
            let challengeDailyTarget = {{ $challenge && $challenge->daily_target ? $challenge->daily_target : 'null' }};
            let challengeTodayLogged = {{ $challenge ? $challenge->today_logged : 0 }};

            /**
             * Calculate bearing between two coordinates
             */
            function calculateBearing(lat1, lon1, lat2, lon2) {
                const toRad = Math.PI / 180;
                const toDeg = 180 / Math.PI;
                const dLon = (lon2 - lon1) * toRad;
                const y = Math.sin(dLon) * Math.cos(lat2 * toRad);
                const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) -
                          Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLon);
                let brng = Math.atan2(y, x) * toDeg;
                return (brng + 360) % 360;
            }

            /**
             * Haversine formula - calculates distance between two GPS coordinates
             * @returns distance in kilometers
             */
            function haversineDistance(lat1, lon1, lat2, lon2) {
                const R = 6371; // Earth radius in km
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            /**
             * Format a distance value (in km) into a human-readable string.
             * Examples:
             *   0.05  → "50 m"
             *   0.5   → "500 m"
             *   1.0   → "1 km"
             *   1.5   → "1 km 500 m"
             *   2.75  → "2 km 750 m"
             */
            function formatDistance(km) {
                const totalMeters = Math.round(km * 1000);
                if (totalMeters < 1000) {
                    return `${totalMeters} m`;
                }
                const wholeKm = Math.floor(totalMeters / 1000);
                const remainingM = totalMeters % 1000;
                if (remainingM === 0) {
                    return `${wholeKm} km`;
                }
                return `${wholeKm} km ${remainingM} m`;
            }

            function startEventTracking(eventId, eventName, autoResume = false) {
                if (autoResume || confirm(`Start GPS monitoring for event: ${eventName}? Please keep this window open while you run.`)) {
                    activeEventId = eventId;
                    activeEventName = eventName;
                    localStorage.setItem('runconnect_active_event_id', eventId);
                    localStorage.setItem('runconnect_active_event_name', eventName);
                    
                    // Do NOT switch to challenge tab — events are fully isolated
                    // Go straight to full-screen map tracking
                    startGpsTracking();
                }
            }

            /**
             * Check and parse GeoJSON route data if event has one
             */
            function eventRouteDataGeojson(event) {
                 if(event && event.route_data) {
                    return '/storage/' + event.route_data;
                 }
                 return null;
            }

            /**
             * Start GPS Tracking — first checks if GPS is available
             */
            function startGpsTracking() {
                // Check if Geolocation API exists
                if (!navigator.geolocation) {
                    showGpsUnavailableModal();
                    return;
                }

                // Test GPS with a quick position request before starting the full tracker
                navigator.geolocation.getCurrentPosition(
                    function(testPosition) {
                        // GPS is working — proceed to start tracking
                        beginGpsTracking();
                    },
                    function(error) {
                        if (error.code === error.PERMISSION_DENIED || error.code === error.POSITION_UNAVAILABLE) {
                            showGpsUnavailableModal();
                        } else {
                            // Timeout — might still work, try anyway
                            beginGpsTracking();
                        }
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            }

            /**
             * Show the "Open on Mobile" modal
             */
            function showGpsUnavailableModal() {
                new bootstrap.Modal(document.getElementById('gpsUnavailableModal')).show();
            }

            /**
             * Actually begin GPS tracking (called after GPS check passes)
             */
            let runnerMap = null;
            let currentEventRouteLayer = null;
            let runnerLiveTracer = null;
            let userCurrentPositionMarker = null;
            let activeEventName = localStorage.getItem('runconnect_active_event_name') || '';

            function beginGpsTracking() {
                // Reset state
                gpsCoords = [];
                totalDistanceKm = 0;
                trackingStartTime = Date.now();
                window.isDrivingModeInitialized = false;
                window._gpsWarmupCount = 0; // Reset warm-up counter

                if (activeEventId) {
                    // ── EVENT TRACKING: Full-screen map only, don't touch challenge sidebar ──
                    enterFullScreenTracking();
                } else {
                    // ── CHALLENGE TRACKING: Use the sidebar UI ──
                    if (document.getElementById('gpsIdle')) document.getElementById('gpsIdle').classList.add('d-none');
                    if (document.getElementById('gpsTracking')) document.getElementById('gpsTracking').classList.remove('d-none');
                    if (document.getElementById('liveDistance')) document.getElementById('liveDistance').textContent = '0 m';
                    if (document.getElementById('runProgressBar')) document.getElementById('runProgressBar').style.width = '0%';
                    if (document.getElementById('runProgressLabel')) document.getElementById('runProgressLabel').textContent = '0%';
                    if (document.getElementById('gpsStatus')) document.getElementById('gpsStatus').textContent = 'Acquiring GPS...';
                }
                
                // Hide View Map for challenges (events are already in full screen)
                if (document.getElementById('btnViewMap')) {
                    document.getElementById('btnViewMap').classList.add('d-none');
                }

                // Start elapsed timer
                timerInterval = setInterval(updateElapsedTime, 1000);

                // Start Live Tracking Broadcast (Event Runs Only — skips if offline)
                liveBroadcastInterval = setInterval(() => {
                    if (activeEventId && lastKnownLocation && navigator.onLine) {
                        fetch('{{ route("tracking.update") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                _token: '{{ csrf_token() }}',
                                event_id: activeEventId,
                                lat: lastKnownLocation.lat,
                                lng: lastKnownLocation.lon,
                                distance: totalDistanceKm,
                                live_route_data: JSON.stringify(gpsCoords)
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'event_ended') {
                                alert('Tracking stopped: This event has ended.');
                                
                                localStorage.removeItem('runconnect_active_event_id');
                                stopGpsTracking();
                                exitFullScreenTracking();
                                
                                if (document.getElementById('gpsTracking')) document.getElementById('gpsTracking').classList.add('d-none');
                                if (document.getElementById('gpsIdle')) document.getElementById('gpsIdle').classList.remove('d-none');
                                
                                window.location.reload();
                            }
                        })
                        .catch(e => console.warn('Broadcast skipped (offline/error):', e.message));
                    }

                    // ── Local Safety Backup: save in-progress run data every cycle ──
                    if (gpsCoords.length > 0) {
                        localStorage.setItem('runconnect_inprogress_run', JSON.stringify({
                            coords: gpsCoords,
                            distance: totalDistanceKm,
                            startTime: trackingStartTime,
                            eventId: activeEventId,
                            savedAt: Date.now()
                        }));
                    }
                }, 5000); // Send coordinates every 5 seconds

                // Start watching position
                gpsWatchId = navigator.geolocation.watchPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        const now = Date.now();

                        // 1. ALWAYS execute UI updates immediately so the user feels the app is responsive
                        if (document.getElementById('gpsStatus')) {
                            document.getElementById('gpsStatus').textContent =
                                accuracy < 10 ? 'GPS Signal: Excellent' :
                                accuracy < 20 ? 'GPS Signal: Good' :
                                accuracy < 30 ? 'GPS Signal: Fair' : 'GPS Signal: Weak';
                        }
                        if (document.getElementById('fsGpsStatus')) {
                            document.getElementById('fsGpsStatus').textContent =
                                accuracy < 10 ? 'GPS: Excellent' :
                                accuracy < 20 ? 'GPS: Good' :
                                accuracy < 30 ? 'GPS: Fair' : 'GPS: Weak';
                        }

                        // Always update the live location marker
                        lastKnownLocation = { lat, lon };
                        if (userCurrentPositionMarker) {
                            userCurrentPositionMarker.setLatLng([lat, lon]);
                            userCurrentPositionMarker.setOpacity(1);
                        }

                        // Always center/pan the map
                        if (runnerMap) {
                            if (!window.isDrivingModeInitialized) {
                                runnerMap.setView([lat, lon], 18, {animate: true});
                                window.isDrivingModeInitialized = true;
                            } else {
                                runnerMap.panTo([lat, lon]);
                            }
                        }

                        // Update UI (time and generic state)
                        updateTrackingUI();

                        // ── Anti-Drift Filter 1: Accuracy Gate ──────────────────────
                        if (accuracy > 30) return;

                        // ── Anti-Drift Filter 2: GPS Warm-Up ─────────────────────────
                        if (!window._gpsWarmupCount) window._gpsWarmupCount = 0;
                        window._gpsWarmupCount++;
                        if (window._gpsWarmupCount <= 3) {
                            gpsCoords.push({ lat, lon, accuracy, time: now });
                            return;
                        }

                        // ── Distance Calculation with Anti-Drift Filters ────────────
                        let accepted = false;
                        if (gpsCoords.length > 0) {
                            const prev = gpsCoords[gpsCoords.length - 1];
                            const dist = haversineDistance(prev.lat, prev.lon, lat, lon);
                            const timeDelta = (now - prev.time) / 1000;

                            if (dist < 0.005) return; // < 5 meters — ignore as jitter
                            if (dist > 0.5) return; // > 500m in one tick — spurious jump

                            if (timeDelta > 0) {
                                const speedKmh = (dist / timeDelta) * 3600;
                                if (speedKmh > 45) return; // Too fast
                            }

                            totalDistanceKm += dist;
                            accepted = true;
                        } else {
                            accepted = true;
                        }

                        if (accepted) {
                            gpsCoords.push({ lat, lon, accuracy, time: now });
                            
                            // Update Live Trace Line ONLY for accepted movement
                            if (runnerLiveTracer) {
                                runnerLiveTracer.addLatLng([lat, lon]);
                            }

                            // Calculate bearing for driving mode arrow
                            if (gpsCoords.length > 1) {
                                const prev = gpsCoords[gpsCoords.length - 2];   
                                const currentBearing = calculateBearing(prev.lat, prev.lon, lat, lon);
                                if (userCurrentPositionMarker) {
                                    const iconEl = userCurrentPositionMarker.getElement();
                                    if (iconEl) {
                                        const arrow = iconEl.querySelector('.nav-arrow');
                                        if (arrow) {
                                            arrow.style.transform = `rotate(${currentBearing - 45}deg)`;
                                        }
                                    }
                                }
                            }
                        }
                    },
                    function(error) {
                        let msg = 'GPS Error';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                msg = 'Location access denied';
                                alert('Please allow location access to track your run.');
                                stopGpsTracking();
                                exitFullScreenTracking();
                                return;
                            case error.POSITION_UNAVAILABLE:
                                msg = 'Position unavailable';
                                break;
                            case error.TIMEOUT:
                                msg = 'GPS timeout - retrying...';
                                break;
                        }
                        if (document.getElementById('gpsStatus')) document.getElementById('gpsStatus').textContent = msg;
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 0,   // Always request fresh GPS data
                        timeout: 10000
                    }
                );
            }

            /**
             * Sets up the Full Screen Tracking Map and overlays UI elements
             */
            function enterFullScreenTracking() {
                const mapContainer = document.getElementById('runnerFullScreenMap');
                const overlayPanel = document.getElementById('runnerStatsOverlay');
                const floatingBtn = document.getElementById('floatingActiveRunBtn');
                const fsFinishBtn = document.getElementById('fsFinishEventBtn');
                const fsEventName = document.getElementById('fsEventName');

                // Apply fullscreen styles to hide scrollbars / body content
                document.body.style.overflow = 'hidden';

                // Setup the Map Container
                mapContainer.classList.remove('d-none');
                mapContainer.style.position = 'fixed';
                mapContainer.style.top = '0';
                mapContainer.style.left = '0';
                mapContainer.style.width = '100vw';
                mapContainer.style.height = '100vh';
                mapContainer.style.zIndex = '9998'; // Below overlay

                const fsEmergencyBtn = document.getElementById('fsEmergencyBtn');

                // Show/hide event-specific UI
                if (activeEventId) {
                    if (fsFinishBtn) fsFinishBtn.classList.remove('d-none');
                    if (fsEmergencyBtn) fsEmergencyBtn.classList.remove('d-none');
                    if (fsEventName && activeEventName) {
                        fsEventName.textContent = activeEventName;
                        fsEventName.classList.remove('d-none');
                    }
                } else {
                    if (fsFinishBtn) fsFinishBtn.classList.add('d-none');
                    if (fsEmergencyBtn) fsEmergencyBtn.classList.add('d-none');
                    if (fsEventName) fsEventName.classList.add('d-none');
                }

                // Setup internal state
                if (!runnerMap) {
                    runnerMap = L.map('runnerFullScreenMap', {
                        zoomControl: false // custom controls if needed
                    }).setView([12.8797, 121.7740], 6); // Default PH center

                    // Define the tile layer with offline capabilities using leaflet.offline
                    const baseLayer = L.tileLayer.offline('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        subdomains: 'abc',
                        minZoom: 13,
                        maxZoom: 19,
                        crossOrigin: true
                    });
                    baseLayer.addTo(runnerMap);

                    const offlineControl = L.control.savetiles(baseLayer, {
                        zoomlevels: [13, 16, 18],
                        confirm(layer, successCallback) {
                            if (window.confirm(`Pre-download map tiles for this area so they work offline?`)) {
                                successCallback();
                            }
                        },
                        confirmRemoval(layer, successCallback) {
                            if (window.confirm("Remove all downloaded offline maps?")) {
                                successCallback();
                            }
                        },
                        saveText: '<i class="fa-solid fa-download" style="color: #4ade80; font-size: 1.25rem;" title="Download Area Offline"></i>',
                        rmText: '<i class="fa-solid fa-trash" style="color: #ef4444; font-size: 1.25rem;" title="Delete Offline Maps"></i>'
                    });
                    offlineControl.addTo(runnerMap);

                    // Move the control's DOM element into our custom header card instead of hovering over the map
                    const mapCardHeader = document.getElementById('fsOfflineBtnContainer');
                    const controlElement = offlineControl.getContainer();
                    if (mapCardHeader && controlElement) {
                        // Strip leaflet positioning classes so it flows naturally in our flexbox header
                        controlElement.classList.remove('leaflet-control', 'leaflet-bar');
                        controlElement.className = 'd-flex align-items-center gap-2';
                        controlElement.style.margin = '0';
                        controlElement.style.border = 'none';
                        controlElement.style.background = 'transparent';
                        controlElement.style.boxShadow = 'none';
                        mapCardHeader.appendChild(controlElement);
                        
                        // Clean up the styling of the inner anchor tags of leaflet-offline
                        const linkEls = controlElement.querySelectorAll('a');
                        linkEls.forEach(linkEl => {
                            linkEl.style.width = '32px';
                            linkEl.style.height = '32px';
                            linkEl.style.lineHeight = '32px';
                            linkEl.style.color = 'inherit';
                            linkEl.style.background = 'rgba(255,255,255,0.05)';
                            linkEl.style.border = '1px solid rgba(255,255,255,0.1)';
                            linkEl.style.borderRadius = '8px';
                            linkEl.style.display = 'flex';
                            linkEl.style.alignItems = 'center';
                            linkEl.style.justifyContent = 'center';
                            linkEl.style.textDecoration = 'none';
                            linkEl.style.cursor = 'pointer';
                        });
                    }

                    // Add live tracer polyline
                    runnerLiveTracer = L.polyline([], {
                         color: '#22c55e', 
                         weight: 5, 
                         opacity: 0.9,
                         dashArray: '10, 10',
                         lineCap: 'round'
                    }).addTo(runnerMap);

                    // Add current position marker (Driving Mode Navigation Arrow)
                    const userDotIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style='background-color:#3b82f6; color:white; width: 36px; height: 36px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 15px rgba(59,130,246,0.5), 0 0 30px rgba(59,130,246,0.2); display: flex; align-items: center; justify-content: center;'><i class='fa-solid fa-location-arrow nav-arrow' style='transition: transform 0.5s ease; transform: rotate(-45deg); font-size: 14px;'></i></div>`,
                        iconSize: [36, 36], iconAnchor: [18, 18]
                    });
                    userCurrentPositionMarker = L.marker([0, 0], {icon: userDotIcon}).addTo(runnerMap);
                    userCurrentPositionMarker.setOpacity(0);
                }

                // Try to show marker at current location if we already have one
                if (userCurrentPositionMarker && lastKnownLocation) {
                    userCurrentPositionMarker.setLatLng([lastKnownLocation.lat, lastKnownLocation.lon]);
                    userCurrentPositionMarker.setOpacity(1);
                    runnerMap.setView([lastKnownLocation.lat, lastKnownLocation.lon], 16, {animate: false});
                }

                // If joining a specific event, let's look up its route if loaded in the DOM list
                if (activeEventId) {
                    const evt = document.querySelector(`.event-track-btn[data-id="${activeEventId}"]`);
                     if (evt && evt.dataset.route) {
                         const routeCacheKey = 'route_cache_event_' + activeEventId;
                         
                         const renderRouteGeoJson = (data) => {
                             if (currentEventRouteLayer) {
                                 runnerMap.removeLayer(currentEventRouteLayer);
                             }
                             currentEventRouteLayer = L.geoJSON(data, {
                                 style: function (feature) {
                                     return {color: "#3b82f6", weight: 4, opacity: 0.7, dashArray: '8, 4'};
                                 },
                                 pointToLayer: function (feature, latlng) {
                                      let isStart = feature.properties.type === 'start';
                                      let color = isStart ? '#22c55e' : '#ef4444';
                                      const icon = L.divIcon({
                                         className: 'custom-div-icon',
                                         html: `<div style='background-color:${color}; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 6px rgba(0,0,0,0.4);'></div>`,
                                         iconSize: [16, 16], iconAnchor: [8, 8]
                                     });
                                     return L.marker(latlng, {icon: icon});
                                 }
                             }).addTo(runnerMap);
                             runnerMap.fitBounds(currentEventRouteLayer.getBounds(), { padding: [80, 80] });
                         };

                         const cachedRoute = localStorage.getItem(routeCacheKey);
                         if (cachedRoute) {
                             try {
                                 renderRouteGeoJson(JSON.parse(cachedRoute));
                             } catch (e) {
                                 console.error("Error parsing cached route", e);
                             }
                         }

                         if (navigator.onLine) {
                             fetch(`/tracking/event/${activeEventId}/locations`)
                                .then(res => {
                                    if (!res.ok) {
                                        throw new Error(`HTTP ${res.status}`);
                                    }
                                    return res.json();
                                })
                                .then(payload => {
                                    if (!payload.route_geojson) {
                                        throw new Error('No route_geojson in response');
                                    }
                                    localStorage.setItem(routeCacheKey, JSON.stringify(payload.route_geojson));
                                    renderRouteGeoJson(payload.route_geojson);
                                }).catch(err => console.error("Error loading route GeoJSON", err));
                         }
                     }
                }

                // Add offline map message fallback if not present
                if (!mapContainer.querySelector('.offline-map-msg')) {
                    const fallbackEl = document.createElement('div');
                    fallbackEl.className = 'offline-map-msg';
                    fallbackEl.style.display = navigator.onLine ? 'none' : 'block';
                    fallbackEl.innerHTML = `
                        <div style="background: rgba(15,23,42,0.9); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px 32px; text-align: center;">
                            <i class="fa-solid fa-satellite-dish fa-2x mb-3" style="color: #4ade80;"></i>
                            <div class="fw-bold text-uppercase text-white" style="font-size: 0.85rem; letter-spacing: 1px;">GPS Tracking Active</div>
                            <div class="mt-1" style="color: rgba(255,255,255,0.5); font-size: 0.75rem;">Map tiles unavailable offline</div>
                        </div>
                    `;
                    mapContainer.appendChild(fallbackEl);

                    // Add listeners to toggle this element dynamically when connection changes
                    window.addEventListener('online', () => fallbackEl.style.display = 'none');
                    window.addEventListener('offline', () => fallbackEl.style.display = 'block');
                } else {
                    mapContainer.querySelector('.offline-map-msg').style.display = navigator.onLine ? 'none' : 'block';
                }

                // Delay invalidate to let browser reflow
                setTimeout(() => { runnerMap.invalidateSize(); }, 300);

                // Setup Overlay Panel
                overlayPanel.classList.remove('d-none');
                overlayPanel.classList.add('d-flex');
                
                // Hide floating button while in full screen
                floatingBtn.classList.add('d-none');
                floatingBtn.classList.remove('d-block');
            }

            function exitFullScreenTracking() {
                const mapContainer = document.getElementById('runnerFullScreenMap');
                const overlayPanel = document.getElementById('runnerStatsOverlay');
                const floatingBtn = document.getElementById('floatingActiveRunBtn');

                document.body.style.overflow = '';
                mapContainer.classList.add('d-none');

                overlayPanel.classList.add('d-none');
                overlayPanel.classList.remove('d-flex');
                
                if (runnerLiveTracer) {
                    runnerLiveTracer.setLatLngs([]); // clear live tail
                }
                if (runnerMap && currentEventRouteLayer) {
                    runnerMap.removeLayer(currentEventRouteLayer);
                    currentEventRouteLayer = null;
                }
                
                // Show floating button if we still have an active event with GPS running
                if (activeEventId && gpsWatchId !== null) {
                    floatingBtn.classList.remove('d-none');
                    floatingBtn.classList.add('d-block');
                }
            }

            function quitAndForfeitEvent() {
                // called from Forfeit confirmation modal
                localStorage.removeItem('runconnect_active_event_id');
                localStorage.removeItem('runconnect_active_event_name');
                stopGpsTracking();
                exitFullScreenTracking();
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('forfeitConfirmModal'));
                if (modal) modal.hide();
                
                window.location.reload();
            }

            /**
             * Update the tracking UI with current distance
             */
            function updateTrackingUI() {
                // Update live distance using smart formatter
                const fmtDist = formatDistance(totalDistanceKm);
                const fmtDistKm = totalDistanceKm.toFixed(2);

                // Full-screen overlay always gets updated
                if (document.getElementById('fsLiveDistance')) {
                    document.getElementById('fsLiveDistance').innerHTML = `${fmtDistKm} <span style="color: rgba(255,255,255,0.4);" class="fs-6">km</span>`;
                }

                if (activeEventId) {
                    // Event tracking — ONLY update the full-screen overlay, not the challenge sidebar
                    return;
                }

                // Challenge tracking — update sidebar UI
                if (document.getElementById('liveDistance')) document.getElementById('liveDistance').textContent = fmtDist;

                // Calculate target for this run session:
                let remainingTarget = 0;
                if (challengeDailyTarget) {
                    remainingTarget = Math.max(0, challengeDailyTarget - challengeTodayLogged);
                } else {
                    remainingTarget = Math.max(0, challengeTargetKm - challengeDistanceLogged);
                }

                const runProgress = remainingTarget > 0
                    ? Math.min(100, Math.round((totalDistanceKm / remainingTarget) * 100))
                    : 100;

                if (document.getElementById('runProgressBar')) document.getElementById('runProgressBar').style.width = runProgress + '%';
                if (document.getElementById('runProgressLabel')) document.getElementById('runProgressLabel').textContent = runProgress + '%';
            }

            /**
             * Update elapsed time display and calculate live pace
             */
            function updateElapsedTime() {
                if (!trackingStartTime) return;
                const elapsed = Math.floor((Date.now() - trackingStartTime) / 1000);
                const hours = String(Math.floor(elapsed / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
                const seconds = String(elapsed % 60).padStart(2, '0');
                
                if (document.getElementById('elapsedTime')) document.getElementById('elapsedTime').textContent = `${hours}:${minutes}:${seconds}`;
                if (document.getElementById('fsElapsedTime')) document.getElementById('fsElapsedTime').textContent = `${hours}:${minutes}:${seconds}`;
                if (document.getElementById('floatingElapsedTime')) document.getElementById('floatingElapsedTime').textContent = `${hours}:${minutes}:${seconds}`;
            }

            /**
             * Stop GPS tracking (internal)
             */
            function stopGpsTracking() {
                if (gpsWatchId !== null) {
                    navigator.geolocation.clearWatch(gpsWatchId);
                    gpsWatchId = null;
                }
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                if (liveBroadcastInterval) {
                    clearInterval(liveBroadcastInterval);
                    liveBroadcastInterval = null;
                }
                activeEventId = null;
                lastKnownLocation = null;
                
                // Ensure floating button is hidden when tracking stops
                const floatingBtn = document.getElementById('floatingActiveRunBtn');
                if (floatingBtn) {
                    floatingBtn.classList.add('d-none');
                    floatingBtn.classList.remove('d-block');
                }
            }



            /**
             * Finish Run - stop tracking and submit distance
             */
            function finishGpsTracking() {
                const wasEventRun = !!activeEventId;
                stopGpsTracking();

                if (wasEventRun) {
                    exitFullScreenTracking();
                    localStorage.removeItem('runconnect_active_event_id');
                    localStorage.removeItem('runconnect_active_event_name');
                }

                // Clear in-progress backup since we're finishing
                localStorage.removeItem('runconnect_inprogress_run');

                const distance = parseFloat(totalDistanceKm.toFixed(2));

                if (distance < 0.01) {
                    if (wasEventRun) {
                        // For events, show an alert and reload instead of challenge sidebar state
                        alert('No significant distance was recorded during this event run. Make sure your GPS is enabled and you are moving.');
                        window.location.reload();
                        return;
                    }
                    // Challenge: show inline "no distance" message inside the card
                    if (document.getElementById('gpsTracking')) document.getElementById('gpsTracking').classList.add('d-none');
                    if (document.getElementById('gpsNoDistance')) document.getElementById('gpsNoDistance').classList.remove('d-none');
                    return;
                }

                // Show submitting state
                if (!wasEventRun) {
                    if (document.getElementById('gpsTracking')) document.getElementById('gpsTracking').classList.add('d-none');
                    if (document.getElementById('gpsSubmitting')) document.getElementById('gpsSubmitting').classList.remove('d-none');
                }

                // Calculate duration
                const durationSeconds = trackingStartTime ? Math.floor((Date.now() - trackingStartTime) / 1000) : 0;
                
                // Prepare Data
                const payload = {
                    distance_km: distance,
                    duration_seconds: durationSeconds,
                    route_data: JSON.stringify(gpsCoords),
                    _token: '{{ csrf_token() }}'
                };

                // Try to Submit
                submitRunData(payload);
            }

            function cancelNoDistanceRun() {
                if (document.getElementById('gpsNoDistance')) document.getElementById('gpsNoDistance').classList.add('d-none');
                if (document.getElementById('gpsIdle')) document.getElementById('gpsIdle').classList.remove('d-none');
                
                // Clear active event if they cancel out
                if (activeEventId) {
                    activeEventId = null;
                    localStorage.removeItem('runconnect_active_event_id');
                    // if they are on the event page, maybe reload?
                    // well the event hasn't finished, they just cancelled measuring.
                }
            }

            function retryGpsTracking() {
                if (document.getElementById('gpsNoDistance')) document.getElementById('gpsNoDistance').classList.add('d-none');
                // Go directly back to tracking — don't show idle, just start again
                startGpsTracking();
            }

            /**
             * Submit run data via AJAX
             * Always tries fetch first — navigator.onLine is unreliable on local/dev servers.
             * Falls back to localStorage only if the network request truly fails.
             */
            async function submitRunData(payload) {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout

                    const response = await fetch('{{ route("challenges.log") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload),
                        signal: controller.signal
                    });

                    clearTimeout(timeoutId);

                    if (response.ok) {
                        // Success — reload to show new state
                        window.location.reload();
                    } else {
                        // Server error (422, 500, etc.) — save locally & show server error feedback
                        const statusCode = response.status;
                        console.warn('Server returned error:', statusCode);
                        saveRunOffline(payload);
                        showSubmitErrorFeedback('server', statusCode);
                    }
                } catch (error) {
                    // Network error (truly offline, DNS fail, timeout, etc.)
                    console.log('Network request failed, saving offline:', error.message);
                    saveRunOffline(payload);
                    showSubmitErrorFeedback('network');

                    // Auto-retry: try to sync immediately after a short delay
                    // in case it was a transient error and internet is actually available
                    setTimeout(() => {
                        attemptAutoSync();
                    }, 3000);
                }
            }

            /**
             * Show error feedback — distinguishes between network vs server errors
             */
            function showSubmitErrorFeedback(type, statusCode) {
                const el = document.getElementById('gpsSubmitting');
                if (!el) return;

                if (type === 'network') {
                    el.innerHTML = `
                        <div class="text-warning mb-3"><i class="fa-solid fa-wifi-slash fa-2x" style="color:#fbbf24;"></i></div>
                        <h5 class="fw-bold" style="color:rgba(255,255,255,0.9);">Connection Issue</h5>
                        <p class="small mb-3" style="color:rgba(255,255,255,0.5);">Could not reach the server. Your run has been saved locally and will auto-sync when connection is restored.</p>
                        <button class="btn fw-bold w-100 mb-2" onclick="manualRetrySync()" style="background:linear-gradient(135deg,#0e6e3e,#1aad6e);color:#fff;border:none;border-radius:50px;padding:12px;">
                            <i class="fa-solid fa-rotate me-1"></i> Try Again Now
                        </button>
                        <button class="btn fw-semibold w-100" onclick="window.location.reload()" style="background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1);border-radius:50px;padding:10px;">
                            OK, Sync Later
                        </button>
                    `;
                } else {
                    el.innerHTML = `
                        <div class="text-danger mb-3"><i class="fa-solid fa-circle-exclamation fa-2x"></i></div>
                        <h5 class="fw-bold" style="color:rgba(255,255,255,0.9);">Server Error (${statusCode || 'Unknown'})</h5>
                        <p class="small mb-3" style="color:rgba(255,255,255,0.5);">The server couldn't process your run right now. It has been saved locally and will auto-sync shortly.</p>
                        <button class="btn fw-bold w-100 mb-2" onclick="manualRetrySync()" style="background:linear-gradient(135deg,#0e6e3e,#1aad6e);color:#fff;border:none;border-radius:50px;padding:12px;">
                            <i class="fa-solid fa-rotate me-1"></i> Retry Now
                        </button>
                        <button class="btn fw-semibold w-100" onclick="window.location.reload()" style="background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1);border-radius:50px;padding:10px;">
                            OK, Sync Later
                        </button>
                    `;
                }
                el.classList.remove('d-none');
            }

            /**
             * Manual retry from the error feedback screen
             */
            async function manualRetrySync() {
                const el = document.getElementById('gpsSubmitting');
                if (el) {
                    el.innerHTML = `
                        <div class="mb-3"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#1aad6e;"></i></div>
                        <h5 class="fw-bold" style="color:rgba(255,255,255,0.9);">Syncing...</h5>
                        <p class="small" style="color:rgba(255,255,255,0.5);">Attempting to upload your run data.</p>
                    `;
                }
                await syncPendingRuns();
            }

            /**
             * Auto-sync attempt — called after a short delay when a submit fails
             */
            async function attemptAutoSync() {
                const pending = JSON.parse(localStorage.getItem('run_connect_pending_runs') || '[]');
                if (pending.length === 0) return;

                try {
                    // Quick connectivity check — try a small HEAD request
                    await fetch('{{ url("/") }}', { method: 'HEAD', mode: 'no-cors', cache: 'no-store' });
                    // If we get here, we have connectivity — sync!
                    console.log('Auto-sync: connectivity detected, syncing pending runs...');
                    syncPendingRuns();
                } catch (e) {
                    console.log('Auto-sync: still no connectivity, will retry on online event.');
                }
            }

            /**
             * Save run to LocalStorage
             */
            function saveRunOffline(payload) {
                const pending = JSON.parse(localStorage.getItem('run_connect_pending_runs') || '[]');
                pending.push({
                    data: payload,
                    timestamp: Date.now()
                });
                localStorage.setItem('run_connect_pending_runs', JSON.stringify(pending));
                checkPendingRuns();
            }

            /**
             * Check and show pending runs
             */
            function checkPendingRuns() {
                const pending = JSON.parse(localStorage.getItem('run_connect_pending_runs') || '[]');
                const alertEl = document.getElementById('offlineSyncAlert');
                const countEl = document.getElementById('pendingRunCount');
                
                if (pending && pending.length > 0) {
                    if (countEl) countEl.textContent = pending.length;
                    if (alertEl) alertEl.classList.remove('d-none');
                } else {
                    if (alertEl) alertEl.classList.add('d-none');
                }
            }

            /**
             * Sync all pending runs
             * Uses a FRESH CSRF token (from the current page load) instead of the stale one
             * that was saved with the offline payload.
             */
            async function syncPendingRuns() {
                const pending = JSON.parse(localStorage.getItem('run_connect_pending_runs') || '[]');
                if (pending.length === 0) return;

                // Get a fresh CSRF token from the current page
                const freshToken = '{{ csrf_token() }}';

                // Update UI if button exists
                const btn = document.getElementById('btnSyncRuns');
                if (btn) {
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Auto-syncing...';
                    btn.disabled = true;
                }
                
                // Show alert if hidden
                const alertEl = document.getElementById('offlineSyncAlert');
                if (alertEl) alertEl.classList.remove('d-none');

                let successCount = 0;
                let failCount = 0;

                // Process sequentially
                for (const item of pending) {
                    try {
                        // Replace stale CSRF token with fresh one
                        const syncPayload = { ...item.data, _token: freshToken };

                        const response = await fetch('{{ route("challenges.log") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': freshToken
                            },
                            body: JSON.stringify(syncPayload)
                        });

                        if (response.ok) {
                            successCount++;
                        } else {
                            failCount++;
                        }
                    } catch (e) {
                        failCount++;
                    }
                }

                // If all success, clear storage
                if (failCount === 0) {
                    localStorage.removeItem('run_connect_pending_runs');
                    window.location.reload();
                } else {
                     localStorage.removeItem('run_connect_pending_runs');
                     alert(`Synced ${successCount} runs. ${failCount} failed.`);
                     window.location.reload();
                }
            }

            // Initialize checks and listeners
            document.addEventListener('DOMContentLoaded', function() {
                checkPendingRuns();
                
                // Auto-sync if online and pending exists
                if (navigator.onLine) {
                    const pending = JSON.parse(localStorage.getItem('run_connect_pending_runs') || '[]');
                    if (pending.length > 0) {
                        syncPendingRuns();
                    }
                }

                @if(session('challenge_complete'))
                    new bootstrap.Modal(document.getElementById('challengeCompleteModal')).show();
                @endif
                @if(session('challenge_failed'))
                    new bootstrap.Modal(document.getElementById('challengeFailedModal')).show();
                @endif

                // Auto-resume Event Tracking if active
                const activeId = localStorage.getItem('runconnect_active_event_id');
                if (activeId) {
                    const btn = document.querySelector(`.event-track-btn[data-id="${activeId}"]`);
                    if (btn) {
                        const evtName = btn.dataset.name || localStorage.getItem('runconnect_active_event_name') || 'Event';
                        startEventTracking(activeId, evtName, true);
                    } else {
                        // Event might be finished or no longer live
                        localStorage.removeItem('runconnect_active_event_id');
                        localStorage.removeItem('runconnect_active_event_name');
                    }
                }

                // Pre-cache event routes if online
                if (navigator.onLine) {
                    document.querySelectorAll('.event-track-btn').forEach(btn => {
                        if (btn.dataset.id) {
                            const eventId = btn.dataset.id;
                            fetch(`/tracking/event/${eventId}/locations`)
                                .then(res => {
                                    if (!res.ok) {
                                        throw new Error(`HTTP ${res.status}`);
                                    }
                                    return res.json();
                                })
                                .then(payload => {
                                    if (!payload.route_geojson) return;
                                    localStorage.setItem('route_cache_event_' + eventId, JSON.stringify(payload.route_geojson));
                                }).catch(e => console.error('Failed to pre-cache route for event:', eventId, e));
                        }
                    });
                }
            });

            // Listen for network recovery
            window.addEventListener('online', function() {
                // Wait a moment for connection to stabilize
                setTimeout(() => {
                    checkPendingRuns();
                    syncPendingRuns();
                }, 1000);
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                stopGpsTracking();
            });
        </script>

    @endpush

    @if(auth()->user()->role === 'organizer')
    <style>
        .btn-outline-custom {
            border-color: #1aad6e !important;
            color: #1aad6e !important;
            transition: all 0.2s ease;
        }
        .btn-outline-custom:hover {
            background-color: rgba(26, 173, 110, 0.1) !important;
            color: #1aad6e !important;
        }
        .btn-check:checked + .btn-outline-custom {
            background-color: #1aad6e !important;
            color: #ffffff !important;
        }
        #organizerTabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            border-bottom: 2px solid rgba(255,255,255,0.05);
        }
        #organizerTabs::-webkit-scrollbar { display: none; }
        #organizerTabs .nav-item { flex: 0 0 auto; }
        #organizerTabs .nav-link {
            color: rgba(255,255,255,0.5);
            border: none;
            border-bottom: 2px solid transparent;
            padding: 12px 20px;
            margin-bottom: -2px;
            transition: all 0.2s;
            border-radius: 0;
            background: transparent;
        }
        #organizerTabs .nav-link:hover {
            color: rgba(255,255,255,0.8);
            border-bottom-color: rgba(255,255,255,0.2);
        }
        #organizerTabs .nav-link.active {
            background: transparent;
            color: #1aad6e;
            border-bottom-color: #1aad6e;
        }
        .organizer-events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .organizer-create-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            min-width: 152px;
        }
        @media (max-width: 576px) {
            .organizer-events-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .organizer-events-header h3 {
                font-size: 1.35rem;
            }
            .organizer-create-btn {
                width: 100%;
                min-width: 0;
                padding: 0.65rem 1rem !important;
            }
        }
    </style>
    <!-- Dashboard Tabs -->
    <ul class="nav nav-tabs mb-4 px-1" id="organizerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="my-events-tab" data-bs-toggle="tab"
                data-bs-target="#my-events" type="button" role="tab" aria-controls="my-events"
                aria-selected="true">
                <i class="fa-solid fa-calendar-alt me-2"></i>My Events
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="participants-tab" data-bs-toggle="tab"
                data-bs-target="#participants" type="button" role="tab" aria-controls="participants"
                aria-selected="false">
                <i class="fa-solid fa-users me-2"></i>Participants
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="statistics-tab" data-bs-toggle="tab"
                data-bs-target="#statistics" type="button" role="tab" aria-controls="statistics"
                aria-selected="false">
                <i class="fa-solid fa-chart-pie me-2"></i>Statistics
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab"
                data-bs-target="#history" type="button" role="tab" aria-controls="history"
                aria-selected="false">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>History
            </button>
        </li>
    </ul>

    <div class="tab-content" id="organizerTabsContent">
        <!-- My Events Tab -->
        <div class="tab-pane fade show active" id="my-events" role="tabpanel" tabindex="0">
            <div class="organizer-events-header mb-4">
                <h3 class="fw-bold mb-0" style="color: #1aad6e;">Manage Events</h3>
                <button class="btn rounded-pill fw-bold shadow-sm px-4 py-2 organizer-create-btn" data-bs-toggle="modal"
                    data-bs-target="#createEventModal" style="background: linear-gradient(135deg, #1aad6e 0%, #0e6e3e 100%); color: white; border: none;">
                    <i class="fa-solid fa-plus me-2"></i> Create Event
                </button>
            </div>

            @if($events->isEmpty())
                <div class="text-center py-5">
                    <div class="mb-3 opacity-50" style="color: rgba(255,255,255,0.5);"><i class="fa-solid fa-calendar-xmark fa-4x"></i></div>
                    <h5 class="fw-bold" style="color: rgba(255,255,255,0.7);">No events created yet.</h5>
                    <p class="small" style="color: rgba(255,255,255,0.4);">Start by creating your first running event!</p>
                </div>
            @else
                @php
                    $activeEvents = $events->where('status', '!=', 'completed');
                @endphp
                
                @if($activeEvents->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-3 opacity-50" style="color: rgba(255,255,255,0.5);"><i class="fa-solid fa-calendar-check fa-4x"></i></div>
                        <h5 class="fw-bold" style="color: rgba(255,255,255,0.7);">No active events.</h5>
                        <p class="small" style="color: rgba(255,255,255,0.4);">Create a new running event!</p>
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

                                    {{-- Gradient Header Banner --}}
                                    <div style="background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%); padding: 18px 20px 14px; position: relative;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 65%;">{{ $event->name }}</h5>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge fw-bold" style="background: rgba(255,255,255,0.2); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                                    {{ $event->formatted_distance }}
                                                </span>
                                                {{-- Action Dropdown --}}
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
                                                                <button type="button" class="dropdown-item small text-success fw-bold" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="showStartEventModal('{{ route('events.toggle-status', $event) }}', '{{ addslashes($event->name) }}')">
                                                                    <i class="fa-solid fa-play me-2"></i> Start Event
                                                                </button>
                                                            @elseif($event->status === 'started')
                                                                <button type="button" class="dropdown-item small text-danger fw-bold" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="showEndEventModal('{{ route('events.toggle-status', $event) }}', '{{ addslashes($event->name) }}')">
                                                                    <i class="fa-solid fa-stop me-2"></i> End Event
                                                                </button>
                                                            @endif
                                                        </li>
                                                        @if($event->status !== 'completed')
                                                            <li><hr class="dropdown-divider" style="border-top-color: rgba(255,255,255,0.1);"></li>
                                                            <li>
                                                                <button class="dropdown-item small text-white" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'"
                                                                    onclick="editEvent({{ $event->id }}, '{{ addslashes($event->name) }}', '{{ addslashes($event->description) }}', '{{ $event->difficulty }}', '{{ $event->distance }}', '{{ $event->registration_start }}', '{{ $event->registration_end }}', '{{ $event->slots }}', '{{ addslashes($event->location ?? '') }}', '{{ $event->event_date }}', '{{ $event->event_time }}', '{{ $event->registration_fee }}')">
                                                                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider" style="border-top-color: rgba(255,255,255,0.1);"></li>
                                                            <li>
                                                                <button class="dropdown-item small text-danger" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick="deleteEvent({{ $event->id }})">
                                                                    <i class="fa-solid fa-trash me-2"></i> Delete
                                                                </button>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- Status chip --}}
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
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0;"><i class="fa-solid fa-tag"></i></span>
                                                <span style="color: rgba(255,255,255,0.65);">{{ $event->registration_fee > 0 ? '₱' . number_format($event->registration_fee, 2) : 'Free' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: #1aad6e; flex-shrink: 0;"><i class="fa-solid fa-users"></i></span>
                                                <span style="color: rgba(255,255,255,0.65);">{{ $event->registrations_count ?? 0 }} / {{ $event->slots }} slots</span>
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
                                            @php $fillRate = $event->slots > 0 ? round(($event->registrations_count / $event->slots) * 100) : 0; @endphp
                                            <span class="fw-bold" style="font-size: 0.72rem; color: {{ $fillRate >= 80 ? '#f87171' : ($fillRate >= 50 ? '#ffc107' : '#1aad6e') }};">
                                                {{ $fillRate }}% filled
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Footer strip --}}
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

        <!-- Participants Tab -->
        <div class="tab-pane fade" id="participants" role="tabpanel" tabindex="0">

            {{-- Event Search Bar --}}
            <div class="card border-0 rounded-4 mb-4" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small text-uppercase mb-0" style="color: rgba(255,255,255,0.5);">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search Event
                        </label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="eventStatusToggle" id="toggleP" value="progressing" autocomplete="off" checked>
                            <label class="btn btn-sm btn-outline-custom rounded-start-pill px-3 fw-bold" for="toggleP">Progressing</label>

                            <input type="radio" class="btn-check" name="eventStatusToggle" id="toggleD" value="completed" autocomplete="off">
                            <label class="btn btn-sm btn-outline-custom rounded-end-pill px-3 fw-bold" for="toggleD">Done</label>
                        </div>
                    </div>
                    <div class="position-relative" id="eventSearchWrapper">
                        <input type="text" class="form-control form-control-lg rounded-pill ps-4 pe-5"
                               id="eventSearchInput" placeholder="Type to search your events..."
                               autocomplete="off" style="background: var(--rc-surface); border: 1px solid var(--rc-border); color: var(--rc-text);">
                        <i class="fa-solid fa-chevron-down position-absolute top-50 translate-middle-y" style="right: 18px; pointer-events:none; color: rgba(255,255,255,0.3);"></i>
                        {{-- Autocomplete Dropdown --}}
                        <div class="list-group position-absolute w-100 shadow-lg rounded-3 overflow-auto d-none" id="eventSearchDropdown" style="z-index: 1050; max-height: 280px; top: calc(100% + 4px); background: var(--rc-surface); border: 1px solid var(--rc-border);">
                            {{-- Items injected by JS --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Selected Event Info Banner --}}
            <div class="alert border-0 rounded-4 d-none mb-4 d-flex align-items-center justify-content-between px-4 py-3" id="selectedEventBanner" style="background: rgba(26,173,110,0.15); border: 1px solid rgba(26,173,110,0.3) !important;">
                <div>
                    <span class="fw-bold" id="selectedEventName" style="color: #1aad6e;"></span>
                    <span class="badge rounded-pill ms-2" id="selectedEventCount" style="background: #1aad6e; color: white;"></span>
                </div>
                <button class="btn btn-sm rounded-pill" onclick="clearEventSearch()" style="border-color: #1aad6e; color: #1aad6e;">
                    <i class="fa-solid fa-xmark me-1"></i> Clear
                </button>
            </div>

            {{-- Participant Table --}}
            <div class="card border-0 rounded-4 overflow-hidden" id="participantTableCard" style="display:none; background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                <div class="border-0 py-3 px-4 d-flex align-items-center justify-content-between" style="background: var(--rc-surface-2); border-bottom: 1px solid var(--rc-border) !important;">
                    <h5 class="fw-bold mb-0" style="color: rgba(255,255,255,0.7);"><i class="fa-solid fa-list me-2"></i>Participants</h5>
                    <div class="position-relative" style="max-width: 300px; width: 100%;">
                        <input type="text" class="form-control form-control-sm rounded-pill ps-5 pe-3" id="participantSearchInput" placeholder="Search runner name, email..." style="background: var(--rc-surface); border: 1px solid var(--rc-border); color: var(--rc-text);">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 translate-middle-y small" style="left: 16px; pointer-events:none; color: rgba(255,255,255,0.3);"></i>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="--bs-table-hover-bg: rgba(255,255,255,0.03);">
                        <thead>
                            <tr style="background: rgba(26,173,110,0.08);">
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">#</th>
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">Runner</th>
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">Size</th>
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">Date</th>
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">Status</th>
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">Payment</th>
                                <th class="border-0 px-4 py-3 small text-uppercase fw-bold" style="color: rgba(255,255,255,0.5);">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="participantTableBody">
                            {{-- Rows injected by JS --}}
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <div class="border-0 d-flex align-items-center justify-content-between px-4 py-3" style="background: var(--rc-surface-2); border-top: 1px solid var(--rc-border) !important;">
                    <small id="paginationInfo" style="color: rgba(255,255,255,0.5);"></small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="paginationControls">
                            {{-- Page buttons injected by JS --}}
                        </ul>
                    </nav>
                </div>
            </div>

            {{-- Empty / Initial State --}}
            <div class="text-center py-5" id="participantEmptyState">
                <div class="mb-3 opacity-50" style="color: rgba(255,255,255,0.5);"><i class="fa-solid fa-users fa-3x"></i></div>
                <h5 class="fw-bold" style="color: rgba(255,255,255,0.7);">Select an event above</h5>
                <p class="small" style="color: rgba(255,255,255,0.4);">Search and select one of your events to view its registered runners.</p>
            </div>

            {{-- Serialize events + registrations data for JS --}}
            <script>
                var _orgEventsData = @json($participantsJson ?? []);
            </script>
        </div>

        <!-- Statistics Tab -->
        <div class="tab-pane fade" id="statistics" role="tabpanel" tabindex="0">

            {{-- Summary Metric Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px; background: rgba(26,173,110,0.15); color: #1aad6e;">
                                <i class="fa-solid fa-calendar-check fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-white" id="stat_total_events">0</h3>
                            <small class="text-uppercase fw-bold" style="color: rgba(255,255,255,0.4);">Total Events</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px; background: rgba(34,197,94,0.15); color: #22c55e;">
                                <i class="fa-solid fa-person-running fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-white" id="stat_total_runners">0</h3>
                            <small class="text-uppercase fw-bold" style="color: rgba(255,255,255,0.4);">Total Runners</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px; background: rgba(255,193,7,0.15); color: #ffc107;">
                                <i class="fa-solid fa-peso-sign fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-white" id="stat_total_revenue">₱0</h3>
                            <small class="text-uppercase fw-bold" style="color: rgba(255,255,255,0.4);">Total Revenue</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px; background: rgba(13,202,240,0.15); color: #0dcaf0;">
                                <i class="fa-solid fa-gauge-high fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-white" id="stat_fill_rate">0%</h3>
                            <small class="text-uppercase fw-bold" style="color: rgba(255,255,255,0.4);">Fill Rate</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts Row --}}
            <div class="row g-4 mb-4">
                {{-- Registration Timeline --}}
                <div class="col-lg-8">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-uppercase small mb-3" style="color: rgba(255,255,255,0.5);">
                                <i class="fa-solid fa-chart-bar me-2"></i>Registrations (Last 7 Days)
                            </h6>
                            <div style="position: relative; height: 250px;">
                                <canvas id="timelineChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Payment Methods Doughnut --}}
                <div class="col-lg-4">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-uppercase small mb-3" style="color: rgba(255,255,255,0.5);">
                                <i class="fa-solid fa-wallet me-2"></i>Payment Methods
                            </h6>
                            <div style="position: relative; height: 250px;">
                                <canvas id="paymentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Second Charts Row --}}
            <div class="row g-4 mb-4">
                {{-- Free vs Paid --}}
                <div class="col-lg-4">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-uppercase small mb-3" style="color: rgba(255,255,255,0.5);">
                                <i class="fa-solid fa-tags me-2"></i>Free vs Paid
                            </h6>
                            <div style="position: relative; height: 250px;">
                                <canvas id="freeVsPaidChart"></canvas>
                            </div>
                            <div class="d-flex justify-content-center gap-4 mt-3">
                                <div class="text-center">
                                    <span class="badge rounded-pill px-3 py-2" id="stat_paid_count" style="background: rgba(34,197,94,0.15); color: #22c55e;">0</span>
                                    <div class="small mt-1" style="color: rgba(255,255,255,0.4);">Paid</div>
                                </div>
                                <div class="text-center">
                                    <span class="badge rounded-pill px-3 py-2" id="stat_free_count" style="background: rgba(108,117,125,0.2); color: #adb5bd;">0</span>
                                    <div class="small mt-1" style="color: rgba(255,255,255,0.4);">Free</div>
                                </div>
                                <div class="text-center">
                                    <span class="badge rounded-pill px-3 py-2" id="stat_cancelled_count" style="background: rgba(220,53,69,0.15); color: #f87171;">0</span>
                                    <div class="small mt-1" style="color: rgba(255,255,255,0.4);">Cancelled</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Per-Event Breakdown Table --}}
                <div class="col-lg-8">
                    <div class="card border-0 rounded-4 h-100" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-uppercase small mb-3" style="color: rgba(255,255,255,0.5);">
                                <i class="fa-solid fa-list-check me-2"></i>Per-Event Breakdown
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="--bs-table-hover-bg: rgba(255,255,255,0.03);">
                                    <thead>
                                        <tr style="background: rgba(26,173,110,0.08);">
                                            <th class="border-0 px-3 py-2 small" style="color: rgba(255,255,255,0.5);">Event</th>
                                            <th class="border-0 px-3 py-2 small text-center" style="color: rgba(255,255,255,0.5);">Runners</th>
                                            <th class="border-0 px-3 py-2 small text-center" style="color: rgba(255,255,255,0.5);">Fee</th>
                                            <th class="border-0 px-3 py-2 small text-end" style="color: rgba(255,255,255,0.5);">Revenue</th>
                                            <th class="border-0 px-3 py-2 small" style="min-width:120px; color: rgba(255,255,255,0.5);">Fill Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody id="perEventTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats data for JS --}}
            <script>
                var _orgStatsData = @json($statsJson ?? []);
            </script>
        </div>
        </div>
        
        <!-- History Tab (Completed Events) -->
        <div class="tab-pane fade" id="history" role="tabpanel" tabindex="0">
            <div class="card border-0 rounded-4 mb-4" style="background: var(--rc-surface-2); border: 1px solid var(--rc-border) !important;">
                <div class="card-body p-4">
                    <label class="form-label fw-bold small text-uppercase mb-2" style="color: rgba(255,255,255,0.5);">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Search Completed Events
                    </label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-lg rounded-pill ps-4 pe-5"
                               id="historySearchInput" placeholder="Type event name..."
                               autocomplete="off" oninput="paginateHistory(1)" style="background: var(--rc-surface); border: 1px solid var(--rc-border); color: var(--rc-text);">
                    </div>
                </div>
            </div>

            <div class="row g-4" id="historyEventsContainer">
                <!-- Rendered by JS -->
            </div>
            
            <div class="d-flex justify-content-center mt-4" id="historyPaginationControls">
                <!-- Pagination buttons rendered by JS -->
            </div>
            
            @php
                $completedEventsJson = $events->where('status', 'completed')->map(function($event) {
                    $difficultyStyle = 'background: rgba(108,117,125,0.15); color: #adb5bd; border: 1px solid rgba(108,117,125,0.3);';
                    if($event->difficulty === 'Beginner') $difficultyStyle = 'background: rgba(26,173,110,0.15); color: #1aad6e; border: 1px solid rgba(26,173,110,0.3);';
                    if($event->difficulty === 'Improving') $difficultyStyle = 'background: rgba(13,202,240,0.12); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3);';
                    if($event->difficulty === 'Intermediate') $difficultyStyle = 'background: rgba(255,193,7,0.12); color: #ffc107; border: 1px solid rgba(255,193,7,0.3);';
                    
                    return [
                        'id' => $event->id,
                        'name' => htmlspecialchars($event->name, ENT_QUOTES),
                        'description' => htmlspecialchars(Str::limit($event->description, 80), ENT_QUOTES),
                        'distance' => $event->distance,
                        'difficulty' => $event->difficulty,
                        'difficultyStyle' => $difficultyStyle,
                        'location' => htmlspecialchars($event->location ?? 'Location TBD', ENT_QUOTES),
                        'event_date' => $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'Date TBD',
                        'event_time' => $event->event_time ? \Carbon\Carbon::parse($event->event_time)->format('h:i A') : null,
                        'fee' => $event->registration_fee > 0 ? '₱' . number_format($event->registration_fee, 2) : 'Free',
                        'slots' => $event->slots,
                        'registered' => $event->registrations_count ?? 0,
                        'eventData' => htmlspecialchars(json_encode($event), ENT_QUOTES, 'UTF-8'),
                    ];
                })->values()->toJson();
            @endphp

            <script>
                const allHistoryEvents = {!! $completedEventsJson !!};
                const historyItemsPerPage = 6;
                let currentHistoryPage = 1;

                function formatHistoryDistance(raw) {
                    const s = String(raw).trim().toLowerCase();
                    if (s.endsWith('m') && !s.endsWith('km')) return String(raw).trim();
                    if (s.endsWith('km')) return String(raw).trim();
                    
                    const val = parseFloat(s);
                    if (!isNaN(val)) {
                        if (val > 0 && val < 1) return Math.round(val * 1000) + ' m';
                        return val + ' km';
                    }
                    return raw + ' km';
                }

                function paginateHistory(page = 1) {
                    currentHistoryPage = page;
                    const query = document.getElementById('historySearchInput').value.toLowerCase();
                    
                    // Filter
                    const filtered = allHistoryEvents.filter(e => e.name.toLowerCase().includes(query));
                    
                    // Slice for page
                    const start = (currentHistoryPage - 1) * historyItemsPerPage;
                    const paginated = filtered.slice(start, start + historyItemsPerPage);
                    const totalPages = Math.ceil(filtered.length / historyItemsPerPage);
                    
                    // Render HTML
                    const container = document.getElementById('historyEventsContainer');
                    if (filtered.length === 0) {
                        container.innerHTML = `
                            <div class="col-12 text-center py-5 rounded-4" style="background: var(--rc-surface-2); border: 1px dashed rgba(255,255,255,0.1);">
                                <div class="mb-3 opacity-25"><i class="fa-solid fa-trophy fa-3x" style="color: var(--rc-text-muted);"></i></div>
                                <h6 class="fw-bold opacity-50" style="color: var(--rc-text);">No completed events found.</h6>
                                <p class="small opacity-40 mb-0" style="color: var(--rc-text-muted);">Completed events will appear here.</p>
                            </div>
                        `;
                    } else {
                        container.innerHTML = paginated.map(event => `
                            <div class="col-md-6 col-lg-4">
                                <div class="rc-event-card rounded-4 h-100 d-flex flex-column" style="background: var(--rc-surface-2); overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform .18s, box-shadow .18s, opacity .18s; opacity: 0.92;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.35)';this.style.opacity='1'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';this.style.opacity='0.92'">

                                    <!-- Muted Gradient Header -->
                                    <div style="background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%); padding: 18px 20px 14px; position: relative;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="fw-bold mb-0 text-white lh-sm" style="font-size: 1rem; max-width: 65%;">${event.name}</h5>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge fw-bold" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 0.75rem; padding: 5px 10px; border-radius: 20px;">
                                                    ${formatHistoryDistance(event.distance)}
                                                </span>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 30px; height: 30px; background: rgba(255,255,255,0.2); border: none;" type="button" data-bs-toggle="dropdown">
                                                        <i class="fa-solid fa-ellipsis-vertical text-white" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3" style="background: var(--rc-surface); border: 1px solid rgba(255,255,255,0.1) !important;">
                                                        <li>
                                                            <button class="dropdown-item small text-white" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'" onclick='viewEventDetails(${event.eventData})'>
                                                                <i class="fa-solid fa-eye me-2" style="color: #1aad6e;"></i> View Details
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Finished chip -->
                                        <span class="badge mt-2 d-inline-flex align-items-center gap-1" style="background: rgba(255,193,7,0.2); color: #ffd866; font-size: 0.68rem; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,193,7,0.3);">
                                            <i class="fa-solid fa-flag-checkered" style="font-size: 0.6rem;"></i> Finished
                                        </span>
                                        <!-- Trophy watermark -->
                                        <div class="position-absolute" style="bottom: 8px; right: 14px; opacity: 0.08;">
                                            <i class="fa-solid fa-trophy fa-3x text-white"></i>
                                        </div>
                                    </div>

                                    <!-- Body -->
                                    <div class="flex-grow-1 px-4 pt-3 pb-2">
                                        <p class="mb-3 fw-semibold" style="color: rgba(255,255,255,0.4); font-size: 0.78rem; letter-spacing: 0.2px;">${event.description}</p>

                                        <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                            <div class="d-flex align-items-start gap-3">
                                                <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0; margin-top: 2px;"><i class="fa-solid fa-location-dot"></i></span>
                                                <span style="color: rgba(255,255,255,0.55); line-height: 1.4;">${event.location}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-regular fa-calendar"></i></span>
                                                <span style="color: rgba(255,255,255,0.55);">${event.event_date}${event.event_time ? ' &middot; ' + event.event_time : ''}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-solid fa-tag"></i></span>
                                                <span style="color: rgba(255,255,255,0.55);">${event.fee}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span style="width: 16px; color: rgba(255,193,7,0.7); flex-shrink: 0;"><i class="fa-solid fa-users"></i></span>
                                                <span style="color: rgba(255,255,255,0.55);">${event.registered} / ${event.slots} runners</span>
                                            </div>
                                        </div>

                                        <div style="border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0;"></div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge fw-semibold" style="font-size: 0.72rem; padding: 5px 12px; border-radius: 20px; ${event.difficultyStyle}">
                                                ${event.difficulty}
                                            </span>
                                            <span class="fw-bold" style="font-size: 0.72rem; color: rgba(255,193,7,0.7);">
                                                <i class="fa-solid fa-flag-checkered me-1"></i> Completed
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Footer strip -->
                                    <div class="text-center py-2" style="background: rgba(255,193,7,0.05); border-top: 1px solid rgba(255,193,7,0.1);">
                                        <small class="fw-bold text-uppercase" style="color: rgba(255,193,7,0.4); font-size: 0.65rem; letter-spacing: 0.8px;">
                                            <i class="fa-solid fa-trophy me-1" style="font-size: 0.55rem;"></i> Event Completed
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    // Render Pagination
                    const pagControls = document.getElementById('historyPaginationControls');
                    if (totalPages > 1) {
                        let btns = '';
                        for(let i = 1; i <= totalPages; i++) {
                            btns += `<button class="btn rounded-circle mx-1" style="width: 40px; height: 40px; ${i === currentHistoryPage ? 'background: linear-gradient(135deg, #1aad6e 0%, #0e6e3e 100%); color: white; border: none;' : 'background: var(--rc-surface-2); color: rgba(255,255,255,0.6); border: 1px solid var(--rc-border);'}" onclick="paginateHistory(${i})">${i}</button>`;
                        }
                        pagControls.innerHTML = btns;
                    } else {
                        pagControls.innerHTML = '';
                    }
                }

                // Initial load
                document.addEventListener('DOMContentLoaded', () => {
                    paginateHistory(1);
                });
            </script>
        </div>
    </div>

        {{-- Create Event Modal --}}
        <style>
            #createEventModal .modal-content, #editEventModal .modal-content {
                background: var(--rc-surface);
                color: var(--rc-text);
            }
            #createEventModal .bg-light, #editEventModal .bg-light {
                background-color: var(--rc-surface-2) !important;
                color: var(--rc-text) !important;
            }
            #createEventModal .text-muted, #editEventModal .text-muted {
                color: var(--rc-text-muted) !important;
            }
            #createEventModal .btn-close, #editEventModal .btn-close {
                filter: invert(1) grayscale(100%) brightness(200%);
            }
            #createEventModal input::placeholder, #editEventModal input::placeholder,
            #createEventModal textarea::placeholder, #editEventModal textarea::placeholder,
            #createEventModal select {
                color: rgba(255, 255, 255, 0.4) !important;
            }
            #createEventModal input:not(.map-search-input), #editEventModal input:not(.map-search-input),
            #createEventModal textarea, #editEventModal textarea,
            #createEventModal select option {
                color: var(--rc-text) !important;
            }
            #createEventModal select, #editEventModal select {
                color: var(--rc-text) !important;
            }
            #createEventModal .btn-outline-primary, #editEventModal .btn-outline-primary {
                color: var(--rc-green);
                border-color: var(--rc-green);
            }
            #createEventModal .btn-outline-primary:hover, #editEventModal .btn-outline-primary:hover {
                background-color: var(--rc-green);
                color: #000;
            }
        </style>
        <div class="modal fade" id="createEventModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-white">Create New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('events.store') }}" method="POST">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Event Name</label>
                                <input type="text" class="form-control bg-light border-0" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Description</label>
                                <textarea class="form-control bg-light border-0" name="description" rows="3"
                                    required></textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Registration Start</label>
                                    <input type="date" class="form-control bg-light border-0" name="registration_start" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Registration End</label>
                                    <input type="date" class="form-control bg-light border-0" name="registration_end" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Location Selection</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="create_region_search" placeholder="Region..." autocomplete="off" onfocus="showHybridDropdown('create','region')" oninput="filterHybridOptions('create','region')">
                                            <input type="hidden" id="create_region">
                                            <div class="hybrid-dropdown" id="create_region_dropdown"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="create_province_search" placeholder="Province..." autocomplete="off" onfocus="showHybridDropdown('create','province')" oninput="filterHybridOptions('create','province')">
                                            <input type="hidden" id="create_province">
                                            <div class="hybrid-dropdown" id="create_province_dropdown"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="create_city_search" placeholder="City/Municipality..." autocomplete="off" onfocus="showHybridDropdown('create','city')" oninput="filterHybridOptions('create','city')">
                                            <input type="hidden" id="create_city">
                                            <div class="hybrid-dropdown" id="create_city_dropdown"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="create_barangay_search" placeholder="Barangay..." autocomplete="off" onfocus="showHybridDropdown('create','barangay')" oninput="filterHybridOptions('create','barangay')">
                                            <input type="hidden" id="create_barangay">
                                            <div class="hybrid-dropdown" id="create_barangay_dropdown"></div>
                                        </div>
                                    </div>
                                </div>
                                <label class="form-label small fw-bold text-uppercase text-muted">Street / Purok / Landmark</label>
                                <input type="text" class="form-control bg-light border-0" id="create_street" placeholder="e.g. Purok 1, Rizal St." oninput="updateLocationText('create')">
                                <input type="hidden" name="location" id="create_location" required>
                            </div>
                            <div class="row g-3 mb-3">
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
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Route (Pin Start & End)</label>
                                <div class="card bg-light border-0 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="fw-bold text-muted text-uppercase"><i class="fa-solid fa-route me-1"></i> Pin route on map</small>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleMap('create_map_container', 'create_map')">
                                            <i class="fa-solid fa-map-location-dot me-1"></i> Open Map
                                        </button>
                                    </div>
                                    <div id="create_map_container" class="d-none">
                                        <div class="input-group input-group-sm mb-2 rounded-0 overflow-hidden" style="border: 1px solid rgba(255,255,255,0.1);">
                                            <input type="text" id="create_map_search" class="form-control bg-light border-0" style="border-radius: 0 !important;" placeholder="Search place (e.g. Barobo)..." onkeypress="if(event.key==='Enter'){event.preventDefault();searchLocation('create');}">
                                            <button class="btn btn-primary px-3" style="border-radius: 0 !important;" type="button" onclick="searchLocation('create')"><i class="fa-solid fa-magnifying-glass text-dark"></i></button>
                                            <button class="btn btn-secondary px-3" style="border-radius: 0 !important; background: rgba(255,255,255,0.05); border: none; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="locateUser('create')" title="My Location"><i class="fa-solid fa-crosshairs text-white"></i></button>
                                            <button class="btn btn-secondary px-3" style="border-radius: 0 !important; background: rgba(255,255,255,0.05); border: none; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="toggleFullScreenMap('create')" title="Full Screen"><i class="fa-solid fa-expand text-white"></i></button>
                                        </div>
                                        <div id="create_map" style="height: 300px; width: 100%; border-radius: 10px;" class="border mb-2"></div>
                                        <input type="hidden" name="manual_route_data" id="create_manual_route_data">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-primary fw-bold" id="create_map_status"><i class="fa-solid fa-circle-info me-1"></i> Click map to set Start Point (Green)</small>
                                            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold" onclick="resetMap('create')">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Reset Pins
                                            </button>
                                        </div>
                                        <div id="create_distance_display" class="mt-2 d-none">
                                            <div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center">
                                                <i class="fa-solid fa-road me-2"></i>
                                                <span class="fw-bold">Estimated Distance: <span id="create_distance_value">--</span> km</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Difficulty</label>
                                    <select class="form-select bg-light border-0" name="difficulty" id="create_difficulty" required>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Improving">Improving</option>
                                        <option value="Intermediate">Intermediate</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Distance (km)</label>
                                    <input type="number" step="0.01" class="form-control bg-light border-0" name="distance"
                                        id="create_distance" readonly placeholder="Auto from map" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Slot Limit</label>
                                    <input type="number" min="1" class="form-control bg-light border-0" name="slots" placeholder="e.g. 100" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 d-flex flex-column flex-md-row align-items-stretch gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-fill">Create Event</button>
                            <button type="button" class="btn rounded-pill text-white px-4 fw-bold flex-fill" style="background: var(--rc-surface-2); border: 1px solid rgba(255,255,255,0.05);" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='var(--rc-surface-2)'" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit Event Modal --}}
        <div class="modal fade" id="editEventModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-white">Edit Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editEventForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Event Name</label>
                                <input type="text" class="form-control bg-light border-0" name="name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Description</label>
                                <textarea class="form-control bg-light border-0" name="description" id="edit_description"
                                    rows="3" required></textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Registration Start</label>
                                    <input type="date" class="form-control bg-light border-0" name="registration_start" id="edit_registration_start" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Registration End</label>
                                    <input type="date" class="form-control bg-light border-0" name="registration_end" id="edit_registration_end" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Location Selection</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="edit_region_search" placeholder="Region..." autocomplete="off" onfocus="showHybridDropdown('edit','region')" oninput="filterHybridOptions('edit','region')">
                                            <input type="hidden" id="edit_region">
                                            <div class="hybrid-dropdown" id="edit_region_dropdown"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="edit_province_search" placeholder="Province..." autocomplete="off" onfocus="showHybridDropdown('edit','province')" oninput="filterHybridOptions('edit','province')">
                                            <input type="hidden" id="edit_province">
                                            <div class="hybrid-dropdown" id="edit_province_dropdown"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="edit_city_search" placeholder="City/Municipality..." autocomplete="off" onfocus="showHybridDropdown('edit','city')" oninput="filterHybridOptions('edit','city')">
                                            <input type="hidden" id="edit_city">
                                            <div class="hybrid-dropdown" id="edit_city_dropdown"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="hybrid-select">
                                            <input type="text" class="form-control form-control-sm bg-light border-0 hybrid-input" id="edit_barangay_search" placeholder="Barangay..." autocomplete="off" onfocus="showHybridDropdown('edit','barangay')" oninput="filterHybridOptions('edit','barangay')">
                                            <input type="hidden" id="edit_barangay">
                                            <div class="hybrid-dropdown" id="edit_barangay_dropdown"></div>
                                        </div>
                                    </div>
                                </div>
                                <label class="form-label small fw-bold text-uppercase text-muted">Street / Purok / Landmark</label>
                                <input type="text" class="form-control bg-light border-0" id="edit_street" placeholder="e.g. Purok 1, Rizal St." oninput="updateLocationText('edit')">
                                <input type="hidden" name="location" id="edit_location" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Event Date</label>
                                    <input type="date" class="form-control bg-light border-0" name="event_date" id="edit_event_date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Event Time</label>
                                    <input type="time" class="form-control bg-light border-0" name="event_time" id="edit_event_time" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Reg. Fee (₱)</label>
                                    <input type="number" step="0.01" class="form-control bg-light border-0" name="registration_fee" id="edit_registration_fee" placeholder="0.00" required>
                                </div>
                            </div>
                            {{-- Route Map Pinning --}}
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Route (Pin Start & End)</label>
                                <div class="card bg-light border-0 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="fw-bold text-muted text-uppercase"><i class="fa-solid fa-route me-1"></i> Pin route on map</small>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleMap('edit_map_container', 'edit_map')">
                                            <i class="fa-solid fa-map-location-dot me-1"></i> Open Map
                                        </button>
                                    </div>
                                    <div id="edit_map_container" class="d-none">
                                        <div class="input-group input-group-sm mb-2 rounded-0 overflow-hidden" style="border: 1px solid rgba(255,255,255,0.1);">
                                            <input type="text" id="edit_map_search" class="form-control bg-light border-0" style="border-radius: 0 !important;" placeholder="Search place (e.g. Barobo)..." onkeypress="if(event.key==='Enter'){event.preventDefault();searchLocation('edit');}">
                                            <button class="btn btn-primary px-3" style="border-radius: 0 !important;" type="button" onclick="searchLocation('edit')"><i class="fa-solid fa-magnifying-glass text-dark"></i></button>
                                            <button class="btn btn-secondary px-3" style="border-radius: 0 !important; background: rgba(255,255,255,0.05); border: none; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="locateUser('edit')" title="My Location"><i class="fa-solid fa-crosshairs text-white"></i></button>
                                            <button class="btn btn-secondary px-3" style="border-radius: 0 !important; background: rgba(255,255,255,0.05); border: none; border-left: 1px solid rgba(255,255,255,0.05) !important;" type="button" onclick="toggleFullScreenMap('edit')" title="Full Screen"><i class="fa-solid fa-expand text-white"></i></button>
                                        </div>
                                        <div id="edit_map" style="height: 300px; width: 100%; border-radius: 10px;" class="border mb-2"></div>
                                        <input type="hidden" name="manual_route_data" id="edit_manual_route_data">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-primary fw-bold" id="edit_map_status"><i class="fa-solid fa-circle-info me-1"></i> Click map to set Start Point (Green)</small>
                                            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold" onclick="resetMap('edit')">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Reset Pins
                                            </button>
                                        </div>
                                        <div id="edit_distance_display" class="mt-2 d-none">
                                            <div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center">
                                                <i class="fa-solid fa-road me-2"></i>
                                                <span class="fw-bold">Estimated Distance: <span id="edit_distance_value">--</span> km</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Difficulty</label>
                                    <select class="form-select bg-light border-0" name="difficulty" id="edit_difficulty"
                                        required>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Improving">Improving</option>
                                        <option value="Intermediate">Intermediate</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Distance (km)</label>
                                    <input type="number" step="0.01" class="form-control bg-light border-0" name="distance"
                                        id="edit_distance" readonly placeholder="Auto from map" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Slot Limit</label>
                                    <input type="number" min="1" class="form-control bg-light border-0" name="slots" id="edit_slots" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 d-flex flex-nowrap align-items-stretch gap-2">
                            <button type="button" class="btn rounded-pill text-white px-4 fw-bold flex-fill" style="background: var(--rc-surface-2); border: 1px solid rgba(255,255,255,0.05);" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='var(--rc-surface-2)'" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn rounded-pill px-4 fw-bold flex-fill text-white" style="background: linear-gradient(135deg, #1aad6e 0%, #0e6e3e 100%); border: none;">Update Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Delete Event Form --}}
        <form id="deleteEventForm" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif

    {{-- View Event Details Modal (available for all roles) --}}
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
    {{-- Registration Modal --}}
    <div class="modal fade" id="registerEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--rc-surface, #111827);">
                {{-- Header --}}
                <div style="background: linear-gradient(135deg, #0e6e3e 0%, #1aad6e 100%); padding: 22px 24px 18px; position: relative;">
                    <button type="button" data-bs-dismiss="modal" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.18);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:1.1rem;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid fa-clipboard-check text-white"></i>
                        <h5 class="modal-title fw-bold text-white mb-0">Event Registration</h5>
                    </div>
                    <p class="mb-0 text-white opacity-75 small" id="reg_event_name"></p>
                </div>
                <form id="registerForm" method="POST">
                    @csrf
                    <div class="modal-body" style="padding: 20px 24px; background: var(--rc-surface, #111827);">

                        {{-- T-Shirt Size Selection --}}
                        <div class="mb-4">
                            <label for="tshirt_size" class="form-label fw-semibold small text-uppercase" style="color: rgba(255,255,255,0.5); letter-spacing: 0.5px;">T-Shirt Size</label>
                            <select class="form-select rounded-3" name="tshirt_size" id="tshirt_size" required style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.9); padding: 12px 16px; font-size: 0.93rem;">
                                <option value="" selected disabled style="background:#1a2236;">Select your size</option>
                                <option value="XS" style="background:#1a2236;">XS - Extra Small</option>
                                <option value="S" style="background:#1a2236;">S - Small</option>
                                <option value="M" style="background:#1a2236;">M - Medium</option>
                                <option value="L" style="background:#1a2236;">L - Large</option>
                                <option value="XL" style="background:#1a2236;">XL - Extra Large</option>
                                <option value="2XL" style="background:#1a2236;">2XL - Double XL</option>
                                <option value="3XL" style="background:#1a2236;">3XL - Triple XL</option>
                            </select>
                            <div class="mt-2 small" style="color: rgba(255,255,255,0.4);">
                                <i class="fa-solid fa-circle-info me-1" style="color: #1aad6e;"></i>
                                We will contact you via your registered phone number regarding T-shirt claiming details.
                            </div>
                        </div>

                        {{-- Free Registration Section --}}
                        <div id="reg_free_section" class="d-none">
                            <div class="text-center py-3">
                                <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;border-radius:50%;background:rgba(26,173,110,0.15);">
                                    <i class="fa-solid fa-gift fa-2x" style="color:#1aad6e;"></i>
                                </div>
                                <h5 class="fw-bold mb-2" style="color:#1aad6e;">This event is FREE!</h5>
                                <p class="small mb-0" style="color:rgba(255,255,255,0.5);">No payment required. Just click the button below to secure your spot.</p>
                            </div>
                        </div>

                        {{-- Paid Registration Section --}}
                        <div id="reg_paid_section" class="d-none">
                            {{-- Amount Display --}}
                            <div class="rounded-3 p-3 mb-4 text-center" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                                <small class="fw-bold text-uppercase d-block mb-1" style="color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 0.6px;">Registration Fee</small>
                                <h3 class="fw-bold mb-0" id="reg_fee_display" style="color: #1aad6e;"></h3>
                            </div>

                            <style>
                                .payment-combo-label {
                                    min-height: 90px;
                                    background: rgba(255, 255, 255, 0.05);
                                    border: 1px solid rgba(255, 255, 255, 0.12);
                                    color: rgba(255, 255, 255, 0.8);
                                    transition: all 0.2s;
                                }
                                .payment-combo-label:hover {
                                    background: rgba(255, 255, 255, 0.1);
                                    border-color: rgba(255, 255, 255, 0.2);
                                }
                                .btn-check:checked + .payment-combo-label {
                                    background: rgba(26, 173, 110, 0.15) !important;
                                    border-color: #1aad6e !important;
                                    color: #1aad6e !important;
                                    box-shadow: 0 0 15px rgba(26, 173, 110, 0.15);
                                }
                                .btn-check:active + .payment-combo-label,
                                .btn-check:focus + .payment-combo-label {
                                    box-shadow: 0 0 0 0.25rem rgba(26, 173, 110, 0.25) !important;
                                }
                                @media (max-width: 576px) {
                                    #registerEventModal .modal-footer {
                                        display: flex;
                                        flex-direction: column;
                                        gap: 10px;
                                    }
                                    #registerEventModal .modal-footer .btn {
                                        width: 100%;
                                        margin: 0 !important;
                                    }
                                }
                            </style>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-uppercase" style="color: rgba(255,255,255,0.5); letter-spacing: 0.5px;">Select Payment Method</label>
                                <div class="row g-3" id="payment_methods">
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="payment_method" value="GCash" id="pm_gcash" class="btn-check" required>
                                        <label class="btn w-100 rounded-3 py-3 d-flex flex-column align-items-center justify-content-center gap-2 payment-combo-label" for="pm_gcash">
                                            <i class="fa-solid fa-mobile-screen" style="font-size:1.5rem;"></i>
                                            <small class="fw-bold">GCash</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="payment_method" value="PayMaya" id="pm_paymaya" class="btn-check">
                                        <label class="btn w-100 rounded-3 py-3 d-flex flex-column align-items-center justify-content-center gap-2 payment-combo-label" for="pm_paymaya">
                                            <i class="fa-solid fa-wallet" style="font-size:1.5rem;"></i>
                                            <small class="fw-bold">PayMaya</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="payment_method" value="Credit/Debit Card" id="pm_card" class="btn-check">
                                        <label class="btn w-100 rounded-3 py-3 d-flex flex-column align-items-center justify-content-center gap-2 payment-combo-label" for="pm_card">
                                            <i class="fa-solid fa-credit-card" style="font-size:1.5rem;"></i>
                                            <small class="fw-bold">Card</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="payment_method" value="Bank Transfer" id="pm_bank" class="btn-check">
                                        <label class="btn w-100 rounded-3 py-3 d-flex flex-column align-items-center justify-content-center gap-2 payment-combo-label" for="pm_bank">
                                            <i class="fa-solid fa-building-columns" style="font-size:1.5rem;"></i>
                                            <small class="fw-bold">Bank</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-3 small mb-0 p-3" style="background: rgba(13,202,240,0.08); border: 1px solid rgba(13,202,240,0.2); color: rgba(255,255,255,0.7);">
                                <i class="fa-solid fa-shield-halved me-2" style="color:#0dcaf0;"></i>
                                <strong style="color:rgba(255,255,255,0.9);">Simulated Payment.</strong> This is a demo — no real charges will be made.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 14px 24px; border-top: 1px solid rgba(255,255,255,0.06) !important; background: var(--rc-surface, #111827);">
                        <button type="button" class="btn fw-semibold" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1);border-radius:50px;padding:10px 24px;">Cancel</button>
                        <button type="submit" class="btn fw-bold px-5" id="reg_submit_btn" style="background:linear-gradient(135deg,#0e6e3e,#1aad6e);color:#fff;border:none;border-radius:50px;padding:10px 24px;">
                            <i class="fa-solid fa-check me-1"></i> <span id="reg_submit_text">Register Now</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script for handling Edit/Delete --}}
    @push('scripts')
        <script>
            // Set auth flag for offline/PWA redirection
            localStorage.setItem('runconnect_auth', 'true');

            // Toggle Events List AND My Events List based on connectivity
            function checkEventsOnlineStatus() {
                const list = document.getElementById('eventsListContainer');
                const msg = document.getElementById('offlineEventsMessage');
                const myList = document.getElementById('myEventsListContainer');
                const myMsg = document.getElementById('offlineMyEventsMessage');

                if (list && msg) {
                    if (navigator.onLine) {
                        list.classList.remove('d-none');
                        msg.classList.add('d-none');
                    } else {
                        list.classList.add('d-none');
                        msg.classList.remove('d-none');
                    }
                }
                if (myList && myMsg) {
                    if (navigator.onLine) {
                        myList.classList.remove('d-none');
                        myMsg.classList.add('d-none');
                    } else {
                        myList.classList.add('d-none');
                        myMsg.classList.remove('d-none');
                    }
                }
            }

            window.addEventListener('online', checkEventsOnlineStatus);
            window.addEventListener('offline', checkEventsOnlineStatus);
            document.addEventListener('DOMContentLoaded', checkEventsOnlineStatus);
            function openRegistration(eventId, eventName, fee) {
                if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
                const form = document.getElementById('registerForm');
                form.action = `/events/${eventId}/register`;

                document.getElementById('reg_event_name').textContent = eventName;

                const freeSection = document.getElementById('reg_free_section');
                const paidSection = document.getElementById('reg_paid_section');

                // Reset payment method selection
                document.querySelectorAll('#payment_methods input').forEach(r => r.checked = false);
                document.getElementById('tshirt_size').value = '';

                const paymentInputs = document.querySelectorAll('input[name="payment_method"]');

                if (fee > 0) {
                    freeSection.classList.add('d-none');
                    paidSection.classList.remove('d-none');
                    document.getElementById('reg_fee_display').textContent = '₱' + fee.toFixed(2);
                    document.getElementById('reg_submit_text').textContent = 'Pay & Register';
                    paymentInputs.forEach(input => {
                        input.disabled = false;
                        input.required = true;
                    });
                } else {
                    paidSection.classList.add('d-none');
                    freeSection.classList.remove('d-none');
                    document.getElementById('reg_submit_text').textContent = 'Register Now';
                    paymentInputs.forEach(input => {
                        input.disabled = true;
                        input.required = false;
                    });
                }

                new bootstrap.Modal(document.getElementById('registerEventModal')).show();
            }

            function viewEventDetails(event) {
                if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
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

                new bootstrap.Modal(document.getElementById('viewEventModal')).show();
            }

            function editEvent(id, name, description, difficulty, distance, regStart, regEnd, slots, location, eventDate, eventTime, regFee) {
                if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
                const form = document.getElementById('editEventForm');
                form.action = `/events/${id}`;

                document.getElementById('edit_name').value = name;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_difficulty').value = difficulty;
                document.getElementById('edit_distance').value = distance;
                document.getElementById('edit_registration_start').value = regStart;
                document.getElementById('edit_registration_end').value = regEnd;
                document.getElementById('edit_slots').value = slots;
                document.getElementById('edit_location').value = location;
                document.getElementById('edit_event_date').value = eventDate;
                document.getElementById('edit_event_time').value = eventTime;
                document.getElementById('edit_registration_fee').value = regFee;

                new bootstrap.Modal(document.getElementById('editEventModal')).show();
            }

            function deleteEvent(id) {
                if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
                if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                    const form = document.getElementById('deleteEventForm');
                    form.action = `/events/${id}`;
                    form.submit();
                }
            }
            // ── Participants Tab: Search, Autocomplete, Pagination ──
            (function() {
                if (typeof _orgEventsData === 'undefined') return;

                const PER_PAGE = 10;
                const input = document.getElementById('eventSearchInput');
                const dropdown = document.getElementById('eventSearchDropdown');
                const banner = document.getElementById('selectedEventBanner');
                const bannerName = document.getElementById('selectedEventName');
                const bannerCount = document.getElementById('selectedEventCount');
                const tableCard = document.getElementById('participantTableCard');
                const tbody = document.getElementById('participantTableBody');
                const emptyState = document.getElementById('participantEmptyState');
                const pageInfo = document.getElementById('paginationInfo');
                const pageControls = document.getElementById('paginationControls');
                const partSearchInput = document.getElementById('participantSearchInput');

                if (!input) return;

                let selectedEvent = null;
                let currentPage = 1;

                // ── Event Filtering Toggle ──
                let currentStatusFilter = 'progressing'; // default
                const eventToggleRadios = document.querySelectorAll('input[name="eventStatusToggle"]');
                eventToggleRadios.forEach(r => {
                    r.addEventListener('change', function() {
                        currentStatusFilter = this.value;
                        clearEventSearch(); // reset view when toggling
                        if(input.value.trim() === '') {
                            // If currently focused, refresh dropdown
                            if(document.activeElement === input) {
                                input.dispatchEvent(new Event('focus'));
                            }
                        } else {
                            input.dispatchEvent(new Event('input'));
                        }
                    });
                });

                // Helper to filter events based on toggle
                function getFilteredOrgEvents() {
                    return _orgEventsData.filter(e => {
                        if (currentStatusFilter === 'completed') {
                            return e.status === 'completed';
                        } else {
                            // progressing means not completed (active, registration, etc)
                            return e.status !== 'completed';
                        }
                    });
                }

                // ── Autocomplete ──
                input.addEventListener('input', function() {
                    const q = this.value.trim().toLowerCase();
                    dropdown.innerHTML = '';
                    if (!q) { dropdown.classList.add('d-none'); return; }

                    const matches = getFilteredOrgEvents().filter(e => e.name.toLowerCase().includes(q));
                    if (matches.length === 0) {
                        dropdown.innerHTML = '<div class="list-group-item small py-3 text-center border-0" style="background: transparent; color: rgba(255,255,255,0.5);">No events found</div>';
                    } else {
                        matches.forEach(ev => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-4 border-0';
                            item.style.cssText = 'background: transparent; color: white; cursor: pointer;';
                            item.onmouseover = function(){ this.style.background = 'rgba(255,255,255,0.05)'; };
                            item.onmouseout = function(){ this.style.background = 'transparent'; };
                            item.innerHTML = `<span class="fw-semibold">${escHtml(ev.name)}</span><span class="badge rounded-pill" style="background: #1aad6e; color: white;">${ev.count}</span>`;
                            item.addEventListener('click', () => selectEvent(ev));
                            dropdown.appendChild(item);
                        });
                    }
                    dropdown.classList.remove('d-none');
                });

                input.addEventListener('focus', function() {
                    if (this.value.trim()) this.dispatchEvent(new Event('input'));
                    else {
                        // Show all events on focus if empty
                        dropdown.innerHTML = '';
                        getFilteredOrgEvents().forEach(ev => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-4 border-0';
                            item.style.cssText = 'background: transparent; color: white; cursor: pointer;';
                            item.onmouseover = function(){ this.style.background = 'rgba(255,255,255,0.05)'; };
                            item.onmouseout = function(){ this.style.background = 'transparent'; };
                            item.innerHTML = `<span class="fw-semibold">${escHtml(ev.name)}</span><span class="badge rounded-pill" style="background: #1aad6e; color: white;">${ev.count}</span>`;
                            item.addEventListener('click', () => selectEvent(ev));
                            dropdown.appendChild(item);
                        });
                        if (getFilteredOrgEvents().length > 0) dropdown.classList.remove('d-none');
                    }
                });

                // Close dropdown on outside click
                document.addEventListener('click', function(e) {
                    if (!document.getElementById('eventSearchWrapper').contains(e.target)) {
                        dropdown.classList.add('d-none');
                    }
                });

                // ── Participant Search ──
                if (partSearchInput) {
                    partSearchInput.addEventListener('input', function() {
                        currentPage = 1;
                        renderTable();
                    });
                }

                // ── Select Event ──
                function selectEvent(ev) {
                    selectedEvent = ev;
                    currentPage = 1;
                    input.value = ev.name;
                    dropdown.classList.add('d-none');

                    if (partSearchInput) partSearchInput.value = ''; // Reset participant search

                    bannerName.textContent = ev.name;
                    bannerCount.textContent = ev.count + ' Runner' + (ev.count !== 1 ? 's' : '');
                    banner.classList.remove('d-none');
                    emptyState.style.display = 'none';
                    tableCard.style.display = '';

                    renderTable();
                }

                // ── Clear ──
                window.clearEventSearch = function() {
                    selectedEvent = null;
                    currentPage = 1;
                    input.value = '';
                    if (partSearchInput) partSearchInput.value = '';
                    banner.classList.add('d-none');
                    tableCard.style.display = 'none';
                    emptyState.style.display = '';
                    tbody.innerHTML = '';
                    pageControls.innerHTML = '';
                    pageInfo.textContent = '';
                };

                // ── Render Table with Pagination ──
                function renderTable() {
                    if (!selectedEvent) return;
                    
                    let regs = selectedEvent.registrations;

                    // Filter by participant search
                    if (partSearchInput) {
                        const q = partSearchInput.value.trim().toLowerCase();
                        if (q) {
                            regs = regs.filter(r => 
                                (r.name && r.name.toLowerCase().includes(q)) || 
                                (r.email && r.email.toLowerCase().includes(q))
                            );
                        }
                    }

                    const total = regs.length;
                    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
                    if (currentPage > totalPages) currentPage = totalPages;

                    const start = (currentPage - 1) * PER_PAGE;
                    const pageItems = regs.slice(start, start + PER_PAGE);

                    // Build rows
                    tbody.innerHTML = '';
                    if (total === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4" style="color: rgba(255,255,255,0.5);">No runners found.</td></tr>';
                    } else {
                        pageItems.forEach((r, i) => {
                            const numDisplay = r.bib_number || String(start + i + 1).padStart(4, '0');

                            const statusBadge = r.status === 'registered'
                                ? '<span class="badge bg-success-subtle text-success rounded-pill px-3">Registered</span>'
                                : '<span class="badge bg-danger-subtle text-danger rounded-pill px-3">Cancelled</span>';

                            let payBadge = '';
                            if (r.payment_status === 'paid') {
                                payBadge = `<span class="badge rounded-pill px-3" style="background: rgba(34,197,94,0.15); color: #22c55e;"><i class="fa-solid fa-check me-1"></i> Paid</span>
                                    <div class="small mt-1" style="color: rgba(255,255,255,0.4);">${escHtml(r.payment_method || '')}</div>
                                    <div class="small" style="font-size:0.7em; color: rgba(255,255,255,0.3);">${escHtml(r.reference || '')}</div>`;
                            } else if (r.payment_status === 'free') {
                                payBadge = '<span class="badge rounded-pill px-3" style="background: rgba(108,117,125,0.2); color: #adb5bd;">Free</span>';
                            } else {
                                payBadge = '<span class="badge rounded-pill px-3" style="background: rgba(255,193,7,0.15); color: #ffc107;">Pending</span>';
                            }

                            const amount = parseFloat(r.amount) > 0 ? '₱' + parseFloat(r.amount).toFixed(2) : '-';

                            const tr = document.createElement('tr');
                            tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                            tr.innerHTML = `
                                <td class="px-4 py-3 fw-bold" style="color: rgba(255,255,255,0.5);">#${escHtml(numDisplay)}</td>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width:36px;height:36px;font-size:0.85rem; background: rgba(26,173,110,0.15); color: #1aad6e;">
                                            ${escHtml(r.initial)}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white">${escHtml(r.name)}</div>
                                            <small style="color: rgba(255,255,255,0.4);">${escHtml(r.email)}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 fw-bold text-white">${escHtml(r.tshirt_size || 'N/A')}</td>
                                <td class="px-4 py-3 small" style="color: rgba(255,255,255,0.5);">${escHtml(r.date)}</td>
                                <td class="px-4 py-3">${statusBadge}</td>
                                <td class="px-4 py-3">${payBadge}</td>
                                <td class="px-4 py-3 fw-bold text-white">${amount}</td>`;
                            tbody.appendChild(tr);
                        });
                    }

                    // Pagination info
                    const showStart = total === 0 ? 0 : start + 1;
                    const showEnd = Math.min(start + PER_PAGE, total);
                    pageInfo.textContent = `Showing ${showStart}–${showEnd} of ${total} runner${total !== 1 ? 's' : ''}`;

                    // Pagination buttons
                    pageControls.innerHTML = '';
                    if (totalPages > 1) {
                        // Prev
                        addPageBtn('«', currentPage > 1 ? currentPage - 1 : null);
                        for (let p = 1; p <= totalPages; p++) {
                            addPageBtn(p, p, p === currentPage);
                        }
                        // Next
                        addPageBtn('»', currentPage < totalPages ? currentPage + 1 : null);
                    }
                }

                function addPageBtn(label, page, active) {
                    const li = document.createElement('li');
                    li.className = 'page-item' + (active ? ' active' : '') + (page === null ? ' disabled' : '');
                    const a = document.createElement('a');
                    a.className = 'page-link border-0 rounded-2 mx-1';
                    a.href = '#';
                    a.textContent = label;
                    if (page !== null && !active) {
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            currentPage = page;
                            renderTable();
                        });
                    } else {
                        a.addEventListener('click', e => e.preventDefault());
                    }
                    li.appendChild(a);
                    pageControls.appendChild(li);
                }

                function escHtml(str) {
                    const d = document.createElement('div');
                    d.textContent = str || '';
                    return d.innerHTML;
                }
            })();
        </script>
    @endpush

    {{-- Chart.js & Statistics Tab Logic --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            (function() {
                if (typeof _orgStatsData === 'undefined' || !_orgStatsData.totalEvents) return;
                const S = _orgStatsData;

                // ── Summary Cards ──
                document.getElementById('stat_total_events').textContent = S.totalEvents;
                document.getElementById('stat_total_runners').textContent = S.totalRunners;
                document.getElementById('stat_total_revenue').textContent = '₱' + Number(S.totalRevenue).toLocaleString('en-PH', {minimumFractionDigits:2});
                document.getElementById('stat_fill_rate').textContent = S.fillRate + '%';
                document.getElementById('stat_paid_count').textContent = S.paidCount;
                document.getElementById('stat_free_count').textContent = S.freeCount;
                document.getElementById('stat_cancelled_count').textContent = S.cancelledCount;

                // Charts need canvas to be visible to render correctly
                // We defer chart creation until the Statistics tab is shown
                let chartsRendered = false;
                const statsTab = document.getElementById('statistics-tab');
                if (statsTab) {
                    statsTab.addEventListener('shown.bs.tab', function() {
                        if (!chartsRendered) {
                            chartsRendered = true;
                            renderCharts();
                        }
                    });
                }

                function renderCharts() {
                    // ── Registration Timeline Bar Chart ──
                    const tlLabels = S.timeline.map(t => t.label);
                    const tlData = S.timeline.map(t => t.count);
                    const tlCtx = document.getElementById('timelineChart');
                    if (tlCtx) {
                        const gradient = tlCtx.getContext('2d').createLinearGradient(0, 0, 0, 220);
                        gradient.addColorStop(0, 'rgba(26, 173, 110, 0.8)');
                        gradient.addColorStop(1, 'rgba(14, 110, 62, 0.4)');
                        new Chart(tlCtx, {
                            type: 'bar',
                            data: {
                                labels: tlLabels,
                                datasets: [{
                                    label: 'Registrations',
                                    data: tlData,
                                    backgroundColor: gradient,
                                    borderRadius: 8,
                                    borderSkipped: false,
                                    barThickness: 32,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: 'rgba(255,255,255,0.5)' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                                    x: { ticks: { font: { size: 11 }, color: 'rgba(255,255,255,0.5)' }, grid: { display: false } }
                                }
                            }
                        });
                    }

                    // ── Payment Methods Doughnut ──
                    const pmLabels = Object.keys(S.paymentMethods || {});
                    const pmData = Object.values(S.paymentMethods || {});
                    const pmColors = ['#1aad6e', '#22c55e', '#ffc107', '#0dcaf0', '#e83e8c', '#6f42c1'];
                    const pmCtx = document.getElementById('paymentChart');
                    if (pmCtx && pmLabels.length > 0) {
                        new Chart(pmCtx, {
                            type: 'doughnut',
                            data: {
                                labels: pmLabels,
                                datasets: [{
                                    data: pmData,
                                    backgroundColor: pmColors.slice(0, pmLabels.length),
                                    borderWidth: 0,
                                    hoverOffset: 8,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 12 }, color: 'rgba(255,255,255,0.6)' } }
                                }
                            }
                        });
                    } else if (pmCtx) {
                        pmCtx.parentElement.innerHTML += '<p class="text-center small mt-4" style="color: rgba(255,255,255,0.5);">No paid registrations yet</p>';
                    }

                    // ── Free vs Paid Pie ──
                    const fpCtx = document.getElementById('freeVsPaidChart');
                    if (fpCtx) {
                        new Chart(fpCtx, {
                            type: 'pie',
                            data: {
                                labels: ['Paid', 'Free', 'Cancelled'],
                                datasets: [{
                                    data: [S.paidCount, S.freeCount, S.cancelledCount],
                                    backgroundColor: ['#22c55e', '#6c757d', '#ef4444'],
                                    borderWidth: 0,
                                    hoverOffset: 8,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                }
                            }
                        });
                    }
                }

                // ── Per-Event Breakdown Table ──
                const tbody = document.getElementById('perEventTableBody');
                if (tbody && S.perEvent) {
                    S.perEvent.forEach(ev => {
                        const fee = parseFloat(ev.fee);
                        const feeText = fee > 0 ? '₱' + fee.toFixed(2) : '<span style="color: rgba(255,255,255,0.4);">Free</span>';
                        const revText = '₱' + Number(ev.revenue).toLocaleString('en-PH', {minimumFractionDigits:2});
                        const fillColor = ev.fill_rate >= 80 ? 'bg-danger' : ev.fill_rate >= 50 ? 'bg-warning' : 'bg-success';
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                        tr.innerHTML = `
                            <td class="px-3 py-2">
                                <span class="fw-semibold text-white">${escHtmlStat(ev.name)}</span>
                                <div class="small" style="color: rgba(255,255,255,0.4);">${ev.slots} slots</div>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="fw-bold text-white">${ev.registered}</span>
                                ${ev.cancelled > 0 ? '<span class="text-danger small ms-1">(' + ev.cancelled + ' cancelled)</span>' : ''}
                            </td>
                            <td class="px-3 py-2 text-center text-white">${feeText}</td>
                            <td class="px-3 py-2 text-end fw-bold text-white">${revText}</td>
                            <td class="px-3 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px; background: rgba(255,255,255,0.1);">
                                        <div class="progress-bar ${fillColor} rounded-pill" style="width:${ev.fill_rate}%"></div>
                                    </div>
                                    <small class="fw-bold" style="min-width:35px; color: rgba(255,255,255,0.5);">${ev.fill_rate}%</small>
                                </div>
                            </td>`;
                        tbody.appendChild(tr);
                    });
                }

                function escHtmlStat(str) {
                    const d = document.createElement('div');
                    d.textContent = str || '';
                    return d.innerHTML;
                }
            })();
        </script>
    @endpush

    {{-- Leaflet JS & Logic --}}
    @push('scripts')
        <style>
            html.map-fullscreen-active,
            body.map-fullscreen-active {
                overflow: hidden !important;
                height: 100% !important;
            }

            .map-fullscreen {
                position: fixed !important;
                inset: 0;
                width: 100vw !important;
                height: 100dvh !important;
                z-index: 1060 !important; /* Higher than modal backdrop */
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: none !important;
                max-height: none !important;
                background: white;
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
        </style>
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        <script>
            function toggleFullScreenMap(type) {
                const mapDiv = document.getElementById(type + '_map');
                const isFullscreen = mapDiv.classList.contains('map-fullscreen');
                
                if (!isFullscreen) {
                    mapDiv.classList.add('map-fullscreen');
                    document.documentElement.classList.add('map-fullscreen-active');
                    document.body.classList.add('map-fullscreen-active');
                    
                    // Add exit button
                    const btn = document.createElement('button');
                    btn.innerHTML = '<i class="fa-solid fa-compress me-1"></i> Exit Full Screen';
                    btn.className = 'btn btn-light shadow-sm fw-bold position-absolute top-0 end-0 m-3';
                    btn.id = type + '_exit_fs_btn';
                    btn.style.zIndex = 10020;
                    btn.onclick = (e) => {
                        e.stopPropagation(); // Prevent map click
                        toggleFullScreenMap(type);
                    };
                    mapDiv.appendChild(btn);

                    // Add Search Bar Overlay
                    const searchContainer = document.createElement('div');
                    searchContainer.id = type + '_fs_search_container';
                    searchContainer.className = 'map-fs-toolbar';
                    
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control shadow-sm map-fs-input';
                    input.placeholder = 'Search location...';
                    input.onkeypress = (e) => {
                        e.stopPropagation();
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            searchLocation(type, input.value);
                        }
                    };
                    // Prevent map click when clicking/typing in input
                    input.onclick = (e) => e.stopPropagation(); 
                    input.onmousedown = (e) => e.stopPropagation();
                    input.ondblclick = (e) => e.stopPropagation();

                    const createBtn = (icon, cls, title, onClick) => {
                        const b = document.createElement('button');
                        b.className = `btn ${cls} shadow-sm map-fs-btn`;
                        b.innerHTML = icon;
                        b.title = title;
                        b.onclick = (e) => {
                            e.preventDefault(); e.stopPropagation();
                            onClick();
                        };
                        b.onmousedown = (e) => e.stopPropagation();
                        return b;
                    };

                    const searchBtn = createBtn('<i class="fa-solid fa-magnifying-glass"></i>', 'btn-primary', 'Search', () => searchLocation(type, input.value));
                    const zoomInBtn = createBtn('<i class="fa-solid fa-plus"></i>', 'btn-light text-dark', 'Zoom In', () => maps[type].zoomIn());
                    const zoomOutBtn = createBtn('<i class="fa-solid fa-minus"></i>', 'btn-light text-dark', 'Zoom Out', () => maps[type].zoomOut());
                    const resetBtn = createBtn('<i class="fa-solid fa-rotate-left"></i>', 'btn-danger text-white', 'Reset Pins', () => {
                        if(confirm('Clear all pins?')) resetMap(type);
                    });

                    searchContainer.appendChild(input);
                    searchContainer.appendChild(searchBtn);
                    searchContainer.appendChild(zoomInBtn);
                    searchContainer.appendChild(zoomOutBtn);
                    searchContainer.appendChild(resetBtn);
                    mapDiv.appendChild(searchContainer);
                    
                } else {
                    mapDiv.classList.remove('map-fullscreen');
                    document.documentElement.classList.remove('map-fullscreen-active');
                    document.body.classList.remove('map-fullscreen-active');
                    const btn = document.getElementById(type + '_exit_fs_btn');
                    if (btn) btn.remove();
                    const searchContainer = document.getElementById(type + '_fs_search_container');
                    if (searchContainer) searchContainer.remove();
                }
                
                setTimeout(() => { if(maps[type]) maps[type].invalidateSize(); }, 200);
            }

            // Auto-select difficulty based on distance (km)
            function autoSelectDifficulty(type, distanceKm) {
                const km = parseFloat(distanceKm);
                let difficulty = 'Beginner';
                if (km > 10.0) {
                    difficulty = 'Intermediate';
                } else if (km > 5.0) {
                    difficulty = 'Improving';
                }
                // Map type prefix to difficulty select ID
                const selectId = type + '_difficulty';
                const select = document.getElementById(selectId);
                if (select) {
                    select.value = difficulty;
                }
            }

            let maps = {};
            let pins = {
                create: { waypoints: [], routeLayer: null },
                edit: { waypoints: [], routeLayer: null }
            };

            function toggleMap(containerId, mapId) {
                const container = document.getElementById(containerId);
                container.classList.toggle('d-none');
                if (!container.classList.contains('d-none')) {
                    const type = mapId.split('_')[0];
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
                const statusEl = document.getElementById(type + '_map_status');

                let isStart = pins[type].waypoints.length === 0;
                let color = isStart ? '#22c55e' : '#ef4444'; // Green for start, Red for others
                let popupText = isStart ? 'Start Point' : `Waypoint ${pins[type].waypoints.length}`;

                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style='background-color:${color}; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 6px rgba(0,0,0,0.6);'></div>`,
                    iconSize: [14, 14], iconAnchor: [7, 7]
                });

                let marker = L.marker(latlng, {icon: icon}).addTo(maps[type]).bindPopup(popupText).openPopup();
                pins[type].waypoints.push(marker);

                if (pins[type].waypoints.length === 1) {
                    statusEl.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> Now click to add more waypoints or End Point (Red)';
                } else if (pins[type].waypoints.length > 1) {
                    statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Calculating road route...';
                    fetchRoadRoute(type);
                }
            }

            function fetchRoadRoute(type) {
                if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
                const waypoints = pins[type].waypoints;
                if (waypoints.length < 2) return;

                const statusEl = document.getElementById(type + '_map_status');
                
                // Construct the coordinates string for OSRM: lon,lat;lon,lat...
                const coordinatesString = waypoints.map(marker => {
                    const latLng = marker.getLatLng();
                    return `${latLng.lng},${latLng.lat}`;
                }).join(';');

                const url = `https://router.project-osrm.org/route/v1/driving/${coordinatesString}?overview=full&geometries=geojson`;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.code !== 'Ok' || !data.routes.length) {
                            statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> No road route found. Try different points.';
                            return;
                        }

                        if (pins[type].routeLayer) {
                            maps[type].removeLayer(pins[type].routeLayer);
                        }

                        const route = data.routes[0];
                        const distanceKm = Math.round(route.distance / 1000);
                        const routeCoords = route.geometry.coordinates.map(c => [c[1], c[0]]); // [lng,lat] -> [lat,lng]

                        // Draw road route on map
                        pins[type].routeLayer = L.polyline(routeCoords, {
                            color: '#3b82f6', weight: 4, opacity: 0.8
                        }).addTo(maps[type]);
                        maps[type].fitBounds(pins[type].routeLayer.getBounds().pad(0.15));

                        // Show distance on map card
                        const distDisplay = document.getElementById(type + '_distance_display');
                        const distValue = document.getElementById(type + '_distance_value');
                        if (distDisplay) distDisplay.classList.remove('d-none');
                        if (distValue) distValue.textContent = distanceKm;

                        // Auto-fill the Distance (km) form field
                        const distInput = document.getElementById(type + '_distance');
                        if (distInput) distInput.value = distanceKm;

                        // Auto-select difficulty based on distance
                        autoSelectDifficulty(type, distanceKm);

                        statusEl.innerHTML = '<i class="fa-solid fa-check-circle me-1 text-success"></i> Road route calculated! ' + distanceKm + ' km';

                        // Generate and store GeoJSON with full road geometry
                        generateRoadGeoJSON(type, route.geometry, distanceKm);
                    })
                    .catch(err => {
                        console.error('OSRM error:', err);
                        statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Route calculation failed. Check internet.';
                    });
            }

            function generateRoadGeoJSON(type, roadGeometry, distanceKm) {
                const waypoints = pins[type].waypoints;
                
                let features = [];
                waypoints.forEach((marker, index) => {
                    const latLng = marker.getLatLng();
                    let pointType = 'waypoint';
                    let name = `Waypoint ${index}`;
                    
                    if (index === 0) {
                        pointType = 'start';
                        name = 'Start Point';
                    } else if (index === waypoints.length - 1) {
                        pointType = 'end';
                        name = 'End Point';
                    }
                    
                    features.push({
                        "type": "Feature",
                        "properties": { "name": name, "type": pointType },
                        "geometry": { "type": "Point", "coordinates": [latLng.lng, latLng.lat] }
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
                if (pins[type].waypoints) {
                    pins[type].waypoints.forEach(marker => maps[type].removeLayer(marker));
                }
                if (pins[type].routeLayer) maps[type].removeLayer(pins[type].routeLayer);
                pins[type] = { waypoints: [], routeLayer: null };
                document.getElementById(type + '_manual_route_data').value = '';
                document.getElementById(type + '_map_status').innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> Click map to set Start Point (Green)';
                // Hide distance display & clear distance field
                const distDisplay = document.getElementById(type + '_distance_display');
                if (distDisplay) distDisplay.classList.add('d-none');
                const distInput = document.getElementById(type + '_distance');
                if (distInput) distInput.value = '';
                // Reset difficulty back to default
                const diffSelect = document.getElementById(type + '_difficulty');
                if (diffSelect) diffSelect.value = 'Beginner';
            }

            function searchLocation(type, overrides = null) {
                if (!navigator.onLine) { if(typeof showOfflineToast === 'function') showOfflineToast(); return; }
                const query = overrides || document.getElementById(type + '_map_search').value;
                if (!query) return;



                const btn = document.querySelector(`#${type}_map_container .btn-primary`);
                const statusEl = document.getElementById(type + '_map_status');
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Searching...';

                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        btn.innerHTML = originalContent;
                        if (data && data.length > 0) {
                            const lat = data[0].lat;
                            const lon = data[0].lon;
                            maps[type].flyTo([lat, lon], 14);
                            statusEl.innerHTML = '<i class="fa-solid fa-check-circle me-1 text-success"></i> Location found: ' + data[0].display_name.split(',')[0];
                        } else {
                            statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Location not found via search.';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        btn.innerHTML = originalContent;
                        statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Error searching location.';
                    });
            }

            function locateUser(type) {
                const statusEl = document.getElementById(type + '_map_status');
                
                if (!navigator.geolocation) {
                    statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> Geolocation not supported.';
                    return;
                }
                
                const btn = document.querySelector(`#${type}_map_container .btn-outline-secondary`);
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Locating you...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        btn.innerHTML = originalContent;
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        // Strict GPS check: reject poor accuracy (likely IP-based)
                        if (accuracy > 200) {
                            statusEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Low accuracy (~${Math.round(accuracy)}m). Use GPS or search manually.`;
                            return;
                        }

                        maps[type].flyTo([lat, lon], 17); // Zoom closer for high accuracy
                        
                        // Add marker with accuracy circle
                        L.circle([lat, lon], { radius: accuracy, color: '#3b82f6', weight: 1, opacity: 0.4, fillOpacity: 0.1 }).addTo(maps[type]);
                        L.circleMarker([lat, lon], {
                            radius: 8,
                            fillColor: "#3b82f6",
                            color: "#fff",
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 1
                        }).addTo(maps[type]).bindPopup(`You are here (Accuracy: ${Math.round(accuracy)}m)`).openPopup();
                        
                        statusEl.innerHTML = `<i class="fa-solid fa-check-circle me-1 text-success"></i> Found you! (Accuracy: ${Math.round(accuracy)}m)`;
                    },
                    (error) => {
                        btn.innerHTML = originalContent;
                        console.error('Geolocation error:', error);
                        let msg = 'Unable to retrieve location.';
                        if (error.code === 1) msg = 'Location permission denied.';
                        else if (error.code === 2) msg = 'GPS signal unavailable.';
                        else if (error.code === 3) msg = 'Location request timed out.';
                        
                        statusEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i> ${msg}`;
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }

            /* ── Hybrid Select Styles ── */
            const hybridStyle = document.createElement('style');
            hybridStyle.textContent = `
                .hybrid-select { position: relative; }
                .hybrid-input { cursor: text; background: var(--rc-surface-2) !important; color: var(--rc-text) !important; }
                .hybrid-input::placeholder { font-size: 0.78rem; color: rgba(255, 255, 255, 0.4) !important; }
                .hybrid-dropdown {
                    display: none; position: absolute; z-index: 1055;
                    background: var(--rc-surface-2); border: 1px solid var(--rc-border);
                    border-radius: 0 0 .375rem .375rem;
                    max-height: 180px; overflow-y: auto; width: 100%;
                    box-shadow: 0 6px 16px rgba(0,0,0,.3);
                }
                .hybrid-dropdown.show { display: block; }
                .hybrid-dropdown .hd-item {
                    padding: 8px 12px; cursor: pointer; font-size: .8rem;
                    border-bottom: 1px solid var(--rc-border); transition: background .15s;
                    color: var(--rc-text);
                }
                .hybrid-dropdown .hd-item:hover { background: rgba(255,255,255,0.1); }
                .hybrid-dropdown .hd-item .hd-match { font-weight: 700; color: var(--rc-green); }
                .hybrid-dropdown .hd-empty {
                    padding: 8px 10px; color: var(--rc-text-muted); font-size: .8rem; font-style: italic;
                }
            `;
            document.head.appendChild(hybridStyle);

            /* ── Location Data Store ── */
            const locData = { regions: [], provinces: [], cities: [], barangays: null };
            let searchTimeout = {};

            // Load small datasets immediately, barangays lazily
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

                if (!navigator.geolocation) {
                    document.querySelectorAll('button[title="My Location"]').forEach(el => el.style.display = 'none');
                }
            });

            async function ensureBarangaysLoaded() {
                if (!locData.barangays) {
                    locData.barangays = await fetch('/data/barangays.json').then(r => r.json());
                }
            }

            /* ── Hybrid Dropdown Helpers ── */
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
                    // Highlight matching text
                    if (q && text.toLowerCase().includes(q)) {
                        const idx = text.toLowerCase().indexOf(q);
                        text = text.substring(0, idx) + '<span class="hd-match">' + text.substring(idx, idx + q.length) + '</span>' + text.substring(idx + q.length);
                    }
                    return `<div class="hd-item" onclick="selectHybridOption('${type}','${level}','${item[cfg.code]}','${item[cfg.label].replace(/'/g, "\\'")}')">${text}</div>`;
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
                // Clear previous selection if user is typing
                document.getElementById(type + '_' + level).value = '';
            }

            function selectHybridOption(type, level, code, label) {
                document.getElementById(type + '_' + level).value = code;
                document.getElementById(type + '_' + level + '_search').value = label;
                document.getElementById(type + '_' + level + '_dropdown').classList.remove('show');

                // Clear child levels
                const idx = levelOrder.indexOf(level);
                for (let i = idx + 1; i < levelOrder.length; i++) {
                    document.getElementById(type + '_' + levelOrder[i]).value = '';
                    document.getElementById(type + '_' + levelOrder[i] + '_search').value = '';
                }

                updateLocationText(type);
            }

            // Close all dropdowns on outside click
            document.addEventListener('click', function(e) {
                if (!e.target.classList.contains('hybrid-input')) {
                    document.querySelectorAll('.hybrid-dropdown.show').forEach(dd => dd.classList.remove('show'));
                }
            });

            /* ── Build & Search Location ── */
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
        </script>
    @endpush

        {{-- Profile Setup Modal (Native App Style Wizard) --}}
    @if(isset($showProfileSetup) && $showProfileSetup)
        <style>
            .rc-wizard-modal .modal-content {
                background: var(--rc-surface);
                color: var(--rc-text);
                border: none;
                border-radius: 0;
            }
            .rc-wizard-header {
                padding: 1.5rem 2rem;
                border-bottom: 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: transparent;
            }
            .rc-step {
                display: none;
                animation: slideIn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
            }
            .rc-step.active {
                display: block;
            }
            @keyframes slideIn {
                from { opacity: 0; transform: translateX(20px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .rc-progress-bar {
                height: 4px;
                background: var(--rc-surface-2);
                border-radius: 4px;
                overflow: hidden;
                margin: 0 2rem 2rem;
            }
            .rc-progress-fill {
                height: 100%;
                background: var(--rc-green);
                width: 25%;
                transition: width 0.4s ease;
            }
            .rc-input {
                background: var(--rc-surface-2) !important;
                border: 1px solid var(--rc-border) !important;
                color: var(--rc-text) !important;
                border-radius: 12px;
                padding: 1rem 1.25rem;
                font-weight: 500;
            }
            .rc-input:focus {
                border-color: var(--rc-green) !important;
                box-shadow: 0 0 0 3px var(--rc-green-glow) !important;
            }
            .rc-input:disabled, .rc-input[readonly] {
                background: rgba(255,255,255,0.03) !important;
                color: var(--rc-text-muted) !important;
                border-color: transparent !important;
                cursor: not-allowed;
            }
            .rc-input::placeholder {
                color: rgba(255, 255, 255, 0.3) !important;
            }
            .rc-wizard-modal .text-muted {
                color: var(--rc-text-muted, #8b949e) !important;
            }
            .rc-wizard-modal .ts-control, 
            .rc-wizard-modal .ts-wrapper.single.input-active .ts-control {
                background: var(--rc-surface-2) !important;
                border: 1px solid var(--rc-border) !important;
                color: var(--rc-text) !important;
                border-radius: 12px;
                padding: 1rem 1.25rem;
            }
            .rc-wizard-modal .ts-dropdown, 
            .rc-wizard-modal .ts-dropdown.single {
                background: var(--rc-surface-2) !important;
                border: 1px solid var(--rc-border) !important;
                color: var(--rc-text) !important;
                border-radius: 12px;
            }
            .rc-wizard-modal .ts-dropdown .option {
                color: var(--rc-text) !important;
                padding: 10px 16px;
            }
            .rc-wizard-modal .ts-dropdown .option:hover, 
            .rc-wizard-modal .ts-dropdown .active {
                background: rgba(255, 255, 255, 0.1) !important;
            }
            .rc-wizard-modal .ts-control input {
                color: var(--rc-text) !important;
            }
            .rc-wizard-modal .item {
                color: var(--rc-text) !important;
            }
            .rc-wizard-modal .ts-wrapper.single .ts-control {
                display: flex;
                align-items: center;
                flex-wrap: nowrap;
                overflow: hidden;
                position: relative;
            }
            .rc-wizard-modal .ts-wrapper.single .ts-control > .item {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
                margin: 0;
            }
            .rc-wizard-modal .ts-wrapper.single .ts-control > input {
                position: relative !important;
                width: auto !important;
                min-width: 2ch !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                opacity: 1 !important;
                pointer-events: auto;
            }
            .rc-label {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: var(--rc-text-muted);
                margin-bottom: 0.6rem;
            }
            .rc-health-check {
                background: var(--rc-surface-2);
                border: 1px solid var(--rc-border);
                border-radius: 12px;
                padding: 1rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .form-check-input:checked + .rc-health-check {
                border-color: var(--rc-green);
                background: rgba(0,210,106, 0.05);
                color: var(--rc-text) !important;
            }
            .pace-slider-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem 1rem;
            }
            .pace-arc-wrapper {
                position: relative;
                width: 280px;
                height: 165px;
                margin-bottom: 1rem;
            }
            .pace-arc-wrapper svg {
                width: 100%;
                height: 100%;
            }
            .pace-arc-bg {
                fill: none;
                stroke: rgba(255,255,255,0.06);
                stroke-width: 14;
                stroke-linecap: round;
            }
            .pace-arc-fill {
                fill: none;
                stroke-width: 14;
                stroke-linecap: round;
                transition: stroke-dashoffset 0.35s cubic-bezier(.4,0,.2,1), stroke 0.35s ease;
                filter: drop-shadow(0 0 8px var(--arc-glow, rgba(0,210,106,0.5)));
            }
            .pace-arc-center {
                position: absolute;
                bottom: 6px;
                left: 50%;
                transform: translateX(-50%);
                text-align: center;
            }
            .pace-arc-value {
                font-size: 2.8rem;
                font-weight: 900;
                color: var(--rc-text);
                line-height: 1;
            }
            .pace-arc-unit {
                font-size: 1rem;
                color: var(--rc-text-muted);
                font-weight: 600;
            }
            .pace-level-label {
                font-size: 0.85rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 1.5px;
                padding: 5px 16px;
                border-radius: 20px;
                display: inline-block;
                transition: all 0.35s ease;
                margin-bottom: 1.25rem;
            }
            .pace-slider {
                -webkit-appearance: none;
                width: 100%;
                height: 6px;
                border-radius: 8px;
                background: var(--rc-surface-2);
                outline: none;
                margin-bottom: 0.5rem;
            }
            .pace-slider::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: var(--rc-green);
                cursor: pointer;
                border: 4px solid var(--rc-surface);
                transition: background 0.3s ease, box-shadow 0.3s ease;
                box-shadow: 0 0 10px rgba(0,210,106,0.4);
            }
            .pace-slider::-moz-range-thumb {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: var(--rc-green);
                cursor: pointer;
                border: 4px solid var(--rc-surface);
            }
            .pace-range-labels {
                display: flex;
                justify-content: space-between;
                width: 100%;
                margin-top: 4px;
                margin-bottom: 1rem;
            }
            .pace-range-labels span {
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                opacity: 0.45;
                transition: opacity 0.3s ease, color 0.3s ease;
            }
            .pace-range-labels span.active-label {
                opacity: 1;
            }
            body {
                overflow: hidden !important;
            }
            .rc-wizard-modal {
                display: block !important;
                background: var(--rc-surface) !important;
                opacity: 1 !important;
                z-index: 99999 !important;
            }
        </style>

        <div class="modal rc-wizard-modal show" id="profileSetupModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="display: block;">
            <div class="modal-dialog modal-fullscreen m-0">
                <div class="modal-content">
                    <div class="rc-wizard-header">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 38px; height: 38px; background: var(--rc-green); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #0D1117;">
                                <i class="fa-solid fa-person-running"></i>
                            </div>
                            <span class="fw-bold" style="font-size: 1.25rem; letter-spacing: 0.5px;">RunConnect</span>
                        </div>
                    </div>

                    <div class="rc-progress-bar">
                        <div class="rc-progress-fill" id="wizardProgress"></div>
                    </div>

                    <div class="modal-body px-4 px-md-5 pb-5 overflow-auto">
                        <form id="profileSetupForm" action="{{ route('dashboard.setup-profile') }}" method="POST" class="mx-auto" style="max-width: 600px;">
                            @csrf
                            <div class="alert alert-danger py-3 mb-4 text-center small fw-bold d-none rounded-3" id="setupError" style="background: rgba(248,81,73,0.1); color: #f85149; border: 1px solid rgba(248,81,73,0.3);"></div>

                            @if(auth()->user()->role === 'organizer')
                            <!-- Organizer Profile Form (Single Step for Organizer) -->
                            <div id="organizerSetup" class="rc-step active">
                                <h3 class="fw-bold mb-4" style="font-size: 1.8rem;">Organizer Details</h3>
                                <p class="text-muted mb-5">Please Provide your organization's legal information to continue.</p>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="rc-label">Organization Name</label>
                                        <input type="text" class="form-control rc-input" name="organization_name" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Email Address (Read Only)</label>
                                        <input type="email" class="form-control rc-input" value="{{ auth()->user()->email }}" disabled readonly>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Phone Number</label>
                                        <input type="tel" class="form-control rc-input org-phone" name="phone_number" required>
                                    </div>
                                    <div class="col-12 mt-5">
                                        <h6 class="fw-bold border-bottom border-dark pb-3 mb-4">Location Mapping</h6>
                                        <input type="hidden" name="address" id="full_address" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">City / Municipality</label>
                                        <select class="form-select rc-input" id="setup_city" required>
                                            <option value="" disabled selected>Select City</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Barangay</label>
                                        <select class="form-select rc-input" id="setup_brgy" required disabled>
                                            <option value="" disabled selected>Select Barangay</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="rc-label">Street / Building Info</label>
                                        <input type="text" class="form-control rc-input" id="setup_street" placeholder="e.g. Unit 4, Corporate Plaza" required>
                                    </div>
                                </div>
                                <div class="mt-5 pt-3 text-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold py-3 w-100" style="font-size: 1.1rem;">
                                        Complete Setup <i class="fa-solid fa-check ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            @else
                            <!-- Step 1: Name Information -->
                            <div id="setupStep1" class="rc-step active">
                                <h3 class="fw-bold mb-3" style="font-size: 2rem;">What's your name?</h3>
                                <p class="text-muted mb-5" style="font-size: 1.05rem;">This will be used for your race bibs and official certificates.</p>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="rc-label">First Name</label>
                                        <input type="text" class="form-control rc-input" name="first_name" required placeholder="Juan">
                                    </div>
                                    <div class="col-12">
                                        <label class="rc-label">Middle Name (Optional)</label>
                                        <input type="text" class="form-control rc-input" name="middle_name" placeholder="Perez">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label class="rc-label">Last Name</label>
                                        <input type="text" class="form-control rc-input" name="last_name" required placeholder="Dela Cruz">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="rc-label">Ext. (Opt)</label>
                                        <input type="text" class="form-control rc-input" name="name_extension" placeholder="Jr., III">
                                    </div>
                                </div>
                                <div class="mt-5 pt-4">
                                    <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold py-3 w-100" id="btnNext1" style="font-size: 1.1rem;">
                                        Next <i class="fa-solid fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Demographics & Health -->
                            <div id="setupStep2" class="rc-step">
                                <h3 class="fw-bold mb-3" style="font-size: 2rem;">A bit about you</h3>
                                <p class="text-muted mb-5" style="font-size: 1.05rem;">This helps us place you in the correct age brackets and race categories.</p>
                                <div class="row g-4 mb-5">
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Age</label>
                                        <input type="number" class="form-control rc-input" name="age" min="10" max="120" required placeholder="25">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Gender</label>
                                        <select class="form-select rc-input" name="gender" required>
                                            <option value="" disabled selected>Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <h6 class="fw-bold mb-4 mt-2" style="color: var(--rc-text); font-size: 1.1rem;">Health Conditions (Optional)</h6>
                                <div class="row g-3">
                                    @php
                                        $conditions = ['Asthma', 'Heart Condition', 'High Blood Pressure', 'Joint Problems', 'Diabetes', 'Recent Injury', 'Other'];
                                    @endphp
                                    @foreach($conditions as $condition)
                                        <div class="col-6 col-md-4 position-relative form-check-reverse">
                                            <input class="form-check-input visually-hidden" type="checkbox" name="health_conditions[]" value="{{ $condition }}" id="cond_{{ Str::slug($condition) }}">
                                            <label class="rc-health-check w-100 h-100 text-center fw-bold small m-0 text-muted" for="cond_{{ Str::slug($condition) }}">
                                                {{ $condition }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <!-- Other Specification Input -->
                                <div id="otherConditionInput" class="mt-4 d-none">
                                    <label class="rc-label">Specify Condition</label>
                                    <input type="text" class="form-control rc-input" name="other_condition_text" placeholder="e.g. Mild Arthritis">
                                </div>
                                
                                <div class="mt-5 pt-4 d-flex gap-3">
                                    <button type="button" class="btn btn-outline-light rounded-pill px-4 fw-bold py-3" style="border-color: var(--rc-border); width: 120px;" id="btnPrev2">Back</button>
                                    <button type="button" class="btn btn-primary flex-grow-1 rounded-pill px-5 fw-bold py-3" id="btnNext2" style="font-size: 1.1rem;">
                                        Next <i class="fa-solid fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 3: Contact & Address -->
                            <div id="setupStep3" class="rc-step">
                                <h3 class="fw-bold mb-3" style="font-size: 2rem;">How do we reach you?</h3>
                                <p class="text-muted mb-5" style="font-size: 1.05rem;">Your contact info and shipping address for event kits and emergency.</p>
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Email Address</label>
                                        {{-- Hidden input since user requested it should not be editable/filled --}}
                                        <input type="email" class="form-control rc-input" value="{{ auth()->user()->email }}" disabled readonly>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Phone Number</label>
                                        <input type="tel" class="form-control rc-input" name="phone_number" required placeholder="09XX XXX XXXX">
                                    </div>
                                    <div class="col-12 mt-5">
                                        <h6 class="fw-bold pb-3 mb-2 text-white" style="border-bottom: 2px solid var(--rc-border);">Your Address</h6>
                                        <input type="hidden" name="address" id="full_address" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">City / Municipality</label>
                                        <select class="form-select rc-input" id="setup_city" required>
                                            <option value="" disabled selected>Select City</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="rc-label">Barangay</label>
                                        <select class="form-select rc-input" id="setup_brgy" required disabled>
                                            <option value="" disabled selected>Select Barangay</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="rc-label">Purok / Street / Subdivision</label>
                                        <input type="text" class="form-control rc-input" id="setup_street" placeholder="e.g. Purok 1, Rizal St." required>
                                    </div>
                                </div>
                                <div class="mt-5 pt-4 d-flex gap-3">
                                    <button type="button" class="btn btn-outline-light rounded-pill px-4 fw-bold py-3" style="border-color: var(--rc-border); width: 120px;" id="btnPrev3">Back</button>
                                    <button type="button" class="btn btn-primary flex-grow-1 rounded-pill px-5 fw-bold py-3" id="btnNext3" style="font-size: 1.1rem;">
                                        Next <i class="fa-solid fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 4: Pace / Phase -->
                            <div id="setupStep4" class="rc-step">
                                <h3 class="fw-bold mb-3 text-center" style="font-size: 2rem;">Your Running Phase</h3>
                                <p class="text-muted mb-4 text-center mx-auto" style="max-width: 420px; font-size: 1.05rem;">Adjust the slider to match your average pace. We'll calculate your baseline fitness level.</p>
                                
                                <div class="pace-slider-container">
                                    {{-- Arc Gauge --}}
                                    <div class="pace-arc-wrapper">
                                        <svg viewBox="0 0 280 165" xmlns="http://www.w3.org/2000/svg">
                                            {{-- Background arc --}}
                                            <path id="arcBg" class="pace-arc-bg"
                                                  d="M 30 150 A 110 110 0 0 1 250 150" />
                                            {{-- Filled arc --}}
                                            <path id="arcFill" class="pace-arc-fill"
                                                  d="M 30 150 A 110 110 0 0 1 250 150"
                                                  stroke="#1aad6e" />
                                            {{-- Tick marks --}}
                                            <g id="arcTicks" opacity="0.25"></g>
                                        </svg>
                                        {{-- Center readout --}}
                                        <div class="pace-arc-center">
                                            <div class="pace-arc-value" id="paceArcValue">8.0</div>
                                            <div class="pace-arc-unit">min/km</div>
                                        </div>
                                    </div>

                                    {{-- Level badge (Hidden as per request) --}}
                                    <div class="text-center d-none">
                                        <span class="pace-level-label" id="paceLevelLabel"></span>
                                    </div>

                                    {{-- Slider --}}
                                    <div class="w-100 px-2" style="max-width: 400px;">
                                        <input type="range" class="pace-slider" dir="rtl" min="3.0" max="15.0" step="0.5" id="pace_minutes" name="pace_minutes" value="15.0">
                                        <div class="pace-range-labels mt-2">
                                            <span style="color: #6b7280; opacity: 1; font-size: 0.85rem;">Walking</span>
                                            <span style="color: #1aad6e; opacity: 1; font-size: 0.85rem;">Runner</span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="pace_km" value="1">
                                </div>

                                <div class="mt-4 pt-2 d-flex gap-3">
                                    <button type="button" class="btn btn-outline-light rounded-pill px-4 fw-bold py-3" style="border-color: var(--rc-border); width: 120px;" id="btnPrev4">Back</button>
                                    <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill px-5 fw-bold py-3" id="btnSubmit" style="font-size: 1.1rem;">
                                        Start Running <i class="fa-solid fa-flag-checkered ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var myModal = new bootstrap.Modal(document.getElementById('profileSetupModal'), {
                        backdrop: 'static', keyboard: false
                    });
                    myModal.show();

                    const errorBox = document.getElementById('setupError');
                    const showError = (msg) => { errorBox.textContent = msg; errorBox.classList.remove('d-none'); errorBox.scrollIntoView({behavior: 'smooth'}); };
                    const hideError = () => { errorBox.classList.add('d-none'); };

                    @if(auth()->user()->role !== 'organizer')
                    // Runner 4-Step Logic
                    const steps = [
                        document.getElementById('setupStep1'),
                        document.getElementById('setupStep2'),
                        document.getElementById('setupStep3'),
                        document.getElementById('setupStep4')
                    ];
                    let currentStep = 0;
                    const progressBar = document.getElementById('wizardProgress');

                    function goToStep(index) {
                        hideError();
                        steps.forEach((s, i) => {
                            if(i === index) {
                                s.classList.add('active');
                            } else {
                                s.classList.remove('active');
                            }
                        });
                        currentStep = index;
                        progressBar.style.width = ((index + 1) / steps.length * 100) + '%';
                        window.scrollTo(0,0);
                    }
                    
                    // Initialize progress bar width
                    goToStep(0);

                    // Navigation Actions
                    document.getElementById('btnNext1').addEventListener('click', () => {
                        let fname = document.querySelector('input[name="first_name"]').value.trim();
                        let lname = document.querySelector('input[name="last_name"]').value.trim();
                        if(!fname || !lname) { showError("First name and Last name are required."); return; }
                        goToStep(1);
                    });
                    
                    document.getElementById('btnPrev2').addEventListener('click', () => goToStep(0));
                    document.getElementById('btnNext2').addEventListener('click', () => {
                        let age = document.querySelector('input[name="age"]').value.trim();
                        let gen = document.querySelector('select[name="gender"]').value;
                        if(!age || !gen) { showError("Age and Gender are required."); return; }
                        if(age < 10 || age > 120) { showError("Please enter a valid age."); return; }
                        goToStep(2);
                    });

                    document.getElementById('btnPrev3').addEventListener('click', () => goToStep(1));
                    document.getElementById('btnNext3').addEventListener('click', () => {
                        let phone = document.querySelector('input[name="phone_number"]').value.trim();
                        let address = document.getElementById('full_address').value.trim();
                        // Tom select check
                        let cty = document.getElementById('setup_city').value;
                        let brg = document.getElementById('setup_brgy').value;
                        let str = document.getElementById('setup_street').value.trim();

                        if(!phone || !cty || !brg || !str) { showError("Phone Number and complete address are required."); return; }
                        goToStep(3);
                    });

                    document.getElementById('btnPrev4').addEventListener('click', () => goToStep(2));

                    // Health 'Other' Condition toggle
                    const conditionCheckboxes = document.querySelectorAll('input[name="health_conditions[]"]');
                    const otherInputDiv = document.getElementById('otherConditionInput');
                    const otherCheckbox = Array.from(conditionCheckboxes).find(cb => cb.value === 'Other');
                    if (otherCheckbox) {
                        otherCheckbox.addEventListener('change', function () {
                            if (this.checked) {
                                otherInputDiv.classList.remove('d-none');
                                document.querySelector('input[name="other_condition_text"]').required = true;
                            } else {
                                otherInputDiv.classList.add('d-none');
                                document.querySelector('input[name="other_condition_text"]').required = false;
                                document.querySelector('input[name="other_condition_text"]').value = '';
                            }
                        });
                    }

                    // ═══ Arc Gauge Pace Slider ═══
                    const pSlider = document.getElementById('pace_minutes');
                    const arcFill = document.getElementById('arcFill');
                    const arcValue = document.getElementById('paceArcValue');
                    const levelLabel = document.getElementById('paceLevelLabel');
                    const sliderThumb = pSlider;

                    // Calculate arc total length
                    const arcPath = arcFill;
                    const arcLength = arcPath.getTotalLength();
                    arcFill.style.strokeDasharray = arcLength;

                    // Simplified purely visual zones (Maps conceptually to the 3 remaining backend tiers + Walking)
                    const paceZones = [
                        { max: 6.25, color: '#1aad6e', glow: 'rgba(0,210,106,0.5)' }, // Runner (Intermediate)
                        { max: 7.50, color: '#ffc107', glow: 'rgba(255,193,7,0.5)' }, // Jogger (Improving)
                        { max: 15.0, color: '#6b7280', glow: 'rgba(107,114,128,0.5)' }, // Walking (Beginner)
                    ];

                    function getZone(pace) {
                        for (const z of paceZones) {
                            if (pace <= z.max) return z;
                        }
                        return paceZones[paceZones.length - 1];
                    }

                    function updateArcGauge(pace) {
                        const min = 3.0, max = 15.0;
                        const pct = 1 - ((pace - min) / (max - min));
                        const offset = arcLength * (1 - pct);
                        arcFill.style.strokeDashoffset = offset;

                        const zone = getZone(pace);
                        
                        // Update arc color
                        arcFill.setAttribute('stroke', zone.color);
                        arcFill.style.setProperty('--arc-glow', zone.glow);
                        arcFill.style.filter = `drop-shadow(0 0 10px ${zone.glow})`;

                        // Update center value
                        arcValue.textContent = parseFloat(pace).toFixed(1);

                        // Update slider thumb color via CSS custom property
                        if(sliderThumb) {
                            sliderThumb.style.setProperty('--thumb-color', zone.color);
                            sliderThumb.style.accentColor = zone.color;
                        }
                    }

                    // Initial render
                    updateArcGauge(parseFloat(pSlider.value));

                    pSlider.addEventListener('input', function() {
                        updateArcGauge(parseFloat(this.value));
                    });
                    @endif

                    // Phone formatting logic remains here
                    const phoneInput = document.querySelector('input[name="phone_number"]');
                    if (phoneInput) {
                        const formatPhoneNumber = (val) => {
                            let cleaned = val.replace(/\D/g, '');
                            if (cleaned.startsWith('63')) cleaned = '0' + cleaned.substring(2);
                            if (cleaned.startsWith('0') && cleaned.length === 11) {
                                return `+63 ${cleaned.substring(1, 4)} ${cleaned.substring(4, 7)} ${cleaned.substring(7, 11)}`;
                            }
                            return val;
                        };
                        phoneInput.addEventListener('input', e => {
                            let d = e.target.value.replace(/\D/g, '');
                            if (d.length === 11 || (d.length === 12 && d.startsWith('63'))) e.target.value = formatPhoneNumber(e.target.value);
                        });
                        phoneInput.addEventListener('blur', e => e.target.value = formatPhoneNumber(e.target.value));
                    }

                    // Address Dropdown Logic
                    const citySelect = document.getElementById('setup_city');
                    const brgySelect = document.getElementById('setup_brgy');
                    const streetInput = document.getElementById('setup_street');
                    const addressHidden = document.getElementById('full_address');

                    if (citySelect) {
                        const cleanLabel = (value) => String(value ?? '')
                            .replace(/\s+/g, ' ')
                            .trim();
                        const trimTrailingSpace = (value) => String(value ?? '').replace(/\s+$/g, '');
                        const sanitizeTomSelectDisplay = (instance) => {
                            if (!instance) return;

                            if (instance.control_input) {
                                instance.control_input.value = trimTrailingSpace(cleanLabel(instance.control_input.value));
                            }

                            const itemEl = instance.control ? instance.control.querySelector('.item') : null;
                            if (itemEl) {
                                itemEl.textContent = trimTrailingSpace(cleanLabel(itemEl.textContent));
                            }
                        };

                        let tsCity = new TomSelect(citySelect, {
                            create: false,
                            maxOptions: null,
                            valueField: 'value',
                            labelField: 'text',
                            searchField: ['text', 'prov', 'reg'],
                            placeholder: 'Search City / Municipality...',
                            onInitialize: function () {
                                const input = this.control_input;
                                if (!input) return;
                                input.addEventListener('keydown', function (e) {
                                    if (e.key === 'Enter') e.preventDefault();
                                });
                                input.addEventListener('input', function () {
                                    input.value = trimTrailingSpace(cleanLabel(input.value));
                                });
                                input.addEventListener('blur', function () {
                                    input.value = trimTrailingSpace(cleanLabel(input.value));
                                });
                                sanitizeTomSelectDisplay(this);
                            },
                        });
                        let tsBrgy = new TomSelect(brgySelect, {
                            create: false,
                            maxOptions: null,
                            valueField: 'value',
                            labelField: 'text',
                            searchField: 'text',
                            placeholder: 'Search Barangay...',
                            onInitialize: function () {
                                const input = this.control_input;
                                if (!input) return;
                                input.addEventListener('keydown', function (e) {
                                    if (e.key === 'Enter') e.preventDefault();
                                });
                                input.addEventListener('input', function () {
                                    input.value = trimTrailingSpace(cleanLabel(input.value));
                                });
                                input.addEventListener('blur', function () {
                                    input.value = trimTrailingSpace(cleanLabel(input.value));
                                });
                                sanitizeTomSelectDisplay(this);
                            },
                        });

                        fetch("{{ route('locations.cities') }}").then(res => res.json()).then(data => {
                            tsCity.addOptions(data.map(city => ({
                                value: city.citymunCode,
                                text: cleanLabel(city.citymunDesc),
                                name: cleanLabel(city.citymunDesc),
                                prov: cleanLabel(city.provDesc),
                                reg: cleanLabel(city.regDesc),
                            })));
                        });

                        tsCity.on('change', function(value) {
                            tsBrgy.clearOptions(); tsBrgy.clear(); tsBrgy.disable();
                            sanitizeTomSelectDisplay(tsCity);
                            if (value) {
                                fetch("{{ route('locations.barangays') }}?city_code=" + value).then(res => res.json()).then(data => {
                                    tsBrgy.addOptions(data.map(brgy => ({
                                        value: brgy.brgyCode,
                                        text: cleanLabel(brgy.brgyDesc),
                                        name: cleanLabel(brgy.brgyDesc),
                                    })));
                                    tsBrgy.enable();
                                });
                            }
                            updateAddress();
                        });

                        tsBrgy.on('change', function() {
                            sanitizeTomSelectDisplay(tsBrgy);
                            updateAddress();
                        });
                        if(streetInput) streetInput.addEventListener('input', updateAddress);

                        function updateAddress() {
                            const cityOpt = tsCity.options[tsCity.getValue()];
                            const brgyOpt = tsBrgy.options[tsBrgy.getValue()];
                            const street = streetInput ? streetInput.value.trim() : '';
                            let parts = [];
                            if (street) parts.push(street);
                            if (brgyOpt) parts.push('Brgy. ' + cleanLabel(brgyOpt.name));
                            if (cityOpt) parts.push(cleanLabel(cityOpt.name));
                            if (cityOpt && cityOpt.prov && cleanLabel(cityOpt.prov) !== cleanLabel(cityOpt.name)) parts.push(cleanLabel(cityOpt.prov));
                            if (cityOpt && cityOpt.reg) parts.push(cleanLabel(cityOpt.reg));
                            if(addressHidden) addressHidden.value = parts.join(', ');
                        }
                    }
                });
            </script>
        @endpush
    @endif

    {{-- Live Monitor Modal --}}
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
                <!-- Split container: 30% Leaderboard, 70% Map -->
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

    <!-- Leaflet JS for Live Monitoring -->
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

    <script>
        let liveMap = null;
        let routePolyline = null;
        let runnerMarkers = {}; // stores active markers by user_id
        let runnerLiveRoutes = {}; // stores active trailed polylines
        let pollingInterval = null;

        /**
         * Opens the Live Monitor modal, initializes the map, and starts polling.
         */
        function openLiveMonitor(eventId, eventName) {
            document.getElementById('liveMonitorTitle').textContent = eventName + ' - Live Monitor';
            const modal = new bootstrap.Modal(document.getElementById('liveMonitorModal'));
            modal.show();

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

        /**
         * Clears the polling when the modal is closed.
         */
        document.getElementById('liveMonitorModal').addEventListener('hidden.bs.modal', function () {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
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
                    document.getElementById('lmRunnerCount').textContent = data.runners.length;
                    
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

                    document.getElementById('runnerRankingList').innerHTML = leaderboardHtml;

                    // Draw Route from backend-provided GeoJSON to avoid /storage access issues
                    if (data.route_geojson && !routePolyline) {
                        routePolyline = 'loading'; // Temporary marker to prevent duplication

                        try {
                            routePolyline = L.geoJSON(data.route_geojson, {
                                style: function (feature) {
                                    return {color: "#3b82f6", weight: 4, opacity: 0.6};
                                },
                                pointToLayer: function (feature, latlng) {
                                     let color = feature.properties.type === 'start' ? '#22c55e' : '#ef4444';
                                     const icon = L.divIcon({
                                        className: 'custom-div-icon',
                                        html: `<div style='background-color:${color}; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 6px rgba(0,0,0,0.6);'></div>`,
                                        iconSize: [14, 14], iconAnchor: [7, 7]
                                    });
                                    return L.marker(latlng, {icon: icon});
                                }
                            }).addTo(liveMap);
                            liveMap.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
                        } catch (err) {
                            console.error('Error loading GeoJSON route', err);
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

        function showStartEventModal(actionUrl, eventName) {
            document.getElementById('startEventModalForm').action = actionUrl;
            document.getElementById('startEventModalName').textContent = eventName;
            var modal = new bootstrap.Modal(document.getElementById('startEventConfirmModal'));
            modal.show();
        }

        function showEndEventModal(actionUrl, eventName) {
            document.getElementById('endEventModalForm').action = actionUrl;
            document.getElementById('endEventModalName').textContent = eventName;
            var modal = new bootstrap.Modal(document.getElementById('endEventConfirmModal'));
            modal.show();
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

        async function reportEmergency() {
            if(!activeEventId) return;
            if(!confirm("Trigger Emergency SOS? Organizers will see your location marked in red.")) return;
            try {
                const response = await fetch('/tracking/emergency', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ event_id: activeEventId })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    alert('Emergency reported successfully.');
                }
            } catch(e) {
                console.error(e);
            }
        }

        // Fix for "Blocked aria-hidden on an element because its descendant retained focus" Chrome warning
        document.addEventListener('hide.bs.modal', function() {
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });
    </script>
@endsection
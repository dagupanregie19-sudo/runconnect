<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return $this->getAdminDashboard($user);
        }

        if ($user->role === 'organizer') {
            return $this->getOrganizerDashboard($user);
        }

        return $this->getUserDashboard($user);
    }

    private function getAdminDashboard($user)
    {
        // Admin gets ALL events from ALL organizers
        $events = \App\Models\Event::with(['registrations.user.runnerProfile', 'organizer'])
            ->withCount('registrations')
            ->latest()
            ->get();

        // Pre-build participant data for JS
        $participantsJson = $events->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'count' => $e->registrations_count,
                'status' => $e->status,
                'slots' => $e->slots,
                'organizer' => $e->organizer ? $e->organizer->username : 'Unknown',
                'registrations' => $e->registrations->map(function ($r, $index) use ($e) {
                    $profile = $r->user->runnerProfile;
                    $slotLength = strlen((string) ($e->slots > 0 ? $e->slots : 1000));
                    return [
                        'bib_number' => str_pad($index + 1, $slotLength, "0", STR_PAD_LEFT),
                        'name' => $profile ? $profile->first_name . ' ' . $profile->last_name : $r->user->username,
                        'initial' => strtoupper(substr($r->user->username, 0, 1)),
                        'email' => $r->user->email,
                        'date' => $r->created_at->format('M d, Y'),
                        'status' => $r->status,
                        'payment_status' => $r->payment_status,
                        'payment_method' => $r->payment_method,
                        'reference' => $r->reference_number,
                        'amount' => $r->amount_paid,
                        'tshirt_size' => $r->tshirt_size ?? 'N/A',
                    ];
                })->values(),
            ];
        })->values();

        // Fetch Organizers for User Management
        $organizers = \App\Models\User::where('role', 'organizer')
            ->withCount('events')
            ->latest()
            ->get();

        // Build statistics data
        $allRegs = $events->flatMap->registrations;
        $activeRegs = $allRegs->where('status', 'registered');
        $cancelledRegs = $allRegs->where('status', 'cancelled');
        $paidRegs = $activeRegs->where('payment_status', 'paid');
        $freeRegs = $activeRegs->where('payment_status', 'free');

        $totalRevenue = $paidRegs->sum('amount_paid');
        $totalSlots = $events->sum('slots');
        $totalRegistered = $activeRegs->count();

        $totalUsers = \App\Models\User::count();
        $totalOrganizers = \App\Models\User::where('role', 'organizer')->count();

        $statsJson = [
            'totalEvents' => $events->count(),
            'totalRunners' => $totalRegistered,
            'totalRevenue' => round($totalRevenue, 2),
            'totalSlots' => $totalSlots,
            'cancelledCount' => $cancelledRegs->count(),
            'paidCount' => $paidRegs->count(),
            'freeCount' => $freeRegs->count(),
            'totalUsers' => $totalUsers,
            'totalOrganizers' => $totalOrganizers,
        ];

        // ── Chart Data ──
        // 1. Event Status Distribution
        $statusDistribution = [
            'upcoming'  => $events->where('status', 'upcoming')->count(),
            'started'   => $events->where('status', 'started')->count(),
            'completed' => $events->where('status', 'completed')->count(),
        ];

        // 2. Events by Difficulty
        $difficultyLevels = ['Beginner', 'Improving', 'Intermediate'];
        $difficultyDistribution = [];
        foreach ($difficultyLevels as $level) {
            $difficultyDistribution[$level] = $events->where('difficulty', $level)->count();
        }

        // 3. Monthly Registrations (last 6 months)
        $monthlyRegs = [];
        $monthlyLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyLabels[] = $month->format('M Y');
            $monthlyRegs[] = $allRegs->filter(function ($r) use ($month) {
                return $r->created_at->month === $month->month && $r->created_at->year === $month->year;
            })->count();
        }

        // 4. Top Events by Registration
        $topEvents = $events->sortByDesc('registrations_count')->take(5)->map(function ($e) {
            return [
                'name' => \Illuminate\Support\Str::limit($e->name, 20),
                'count' => $e->registrations_count,
                'slots' => $e->slots,
            ];
        })->values();

        // 5. Revenue breakdown (paid vs free)
        $revenueData = [
            'paid' => $paidRegs->count(),
            'free' => $freeRegs->count(),
            'cancelled' => $cancelledRegs->count(),
        ];

        $chartData = [
            'statusDistribution' => $statusDistribution,
            'difficultyDistribution' => $difficultyDistribution,
            'monthlyLabels' => $monthlyLabels,
            'monthlyRegs' => $monthlyRegs,
            'topEvents' => $topEvents,
            'revenueData' => $revenueData,
        ];

        return view('dashboard.admin.index', compact('user', 'events', 'participantsJson', 'statsJson', 'organizers', 'chartData'));
    }

    private function getOrganizerDashboard($user)
    {
        $showProfileSetup = false;
        if (!$user->organizerProfile) {
            $showProfileSetup = true;
        }

        $events = \App\Models\Event::where('organizer_id', $user->id)
            ->with(['registrations.user.runnerProfile', 'organizer'])
            ->withCount('registrations')
            ->latest()
            ->get();

        // Pre-build participant data for JS
        $participantsJson = $events->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'count' => $e->registrations_count,
                'status' => $e->status,
                'slots' => $e->slots,
                'registrations' => $e->registrations->map(function ($r, $index) use ($e) {
                    $profile = $r->user->runnerProfile;
                    $slotLength = strlen((string) ($e->slots > 0 ? $e->slots : 1000));
                    return [
                        'bib_number' => str_pad($index + 1, $slotLength, "0", STR_PAD_LEFT),
                        'name' => $profile ? $profile->first_name . ' ' . $profile->last_name : $r->user->username,
                        'initial' => strtoupper(substr($r->user->username, 0, 1)),
                        'email' => $r->user->email,
                        'date' => $r->created_at->format('M d, Y'),
                        'status' => $r->status,
                        'payment_status' => $r->payment_status,
                        'payment_method' => $r->payment_method,
                        'reference' => $r->reference_number,
                        'amount' => $r->amount_paid,
                        'tshirt_size' => $r->tshirt_size ?? 'N/A',
                    ];
                })->values(),
            ];
        })->values();

        // Build statistics data
        $allRegs = $events->flatMap->registrations;
        $activeRegs = $allRegs->where('status', 'registered');
        $cancelledRegs = $allRegs->where('status', 'cancelled');
        $paidRegs = $activeRegs->where('payment_status', 'paid');
        $freeRegs = $activeRegs->where('payment_status', 'free');

        $totalRevenue = $paidRegs->sum('amount_paid');
        $totalSlots = $events->sum('slots');
        $totalRegistered = $activeRegs->count();

        // Payment method distribution
        $paymentMethods = $paidRegs->groupBy('payment_method')->map->count();

        // Per-event breakdown
        $perEventStats = $events->map(function ($e) {
            $active = $e->registrations->where('status', 'registered');
            $revenue = $active->where('payment_status', 'paid')->sum('amount_paid');
            $fillRate = $e->slots > 0 ? round(($active->count() / $e->slots) * 100) : 0;
            return [
                'name' => $e->name,
                'slots' => $e->slots,
                'registered' => $active->count(),
                'cancelled' => $e->registrations->where('status', 'cancelled')->count(),
                'revenue' => $revenue,
                'fill_rate' => $fillRate,
                'fee' => $e->registration_fee,
            ];
        })->values();

        // Registration timeline (last 7 days)
        $timeline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = $allRegs->filter(function ($r) use ($date) {
                return $r->created_at->toDateString() === $date->toDateString();
            })->count();
            $timeline[] = [
                'label' => $date->format('M d'),
                'count' => $count,
            ];
        }

        $statsJson = [
            'totalEvents' => $events->count(),
            'totalRunners' => $totalRegistered,
            'totalRevenue' => round($totalRevenue, 2),
            'totalSlots' => $totalSlots,
            'cancelledCount' => $cancelledRegs->count(),
            'paidCount' => $paidRegs->count(),
            'freeCount' => $freeRegs->count(),
            'avgFee' => $events->count() > 0 ? round($events->avg('registration_fee'), 2) : 0,
            'fillRate' => $totalSlots > 0 ? round(($totalRegistered / $totalSlots) * 100) : 0,
            'paymentMethods' => $paymentMethods,
            'perEvent' => $perEventStats,
            'timeline' => $timeline,
        ];

        // Defaults for shared blade (challenge tab is user-only)
        $challenge = null;
        $lastChallenge = null;
        $challengeLevels = \App\Models\UserChallenge::LEVELS;

        return view('dashboard.user.index', compact('user', 'showProfileSetup', 'events', 'participantsJson', 'statsJson', 'challenge', 'lastChallenge', 'challengeLevels'));
    }

    private function getUserDashboard($user)
    {
        // Check if user has a runner profile
        $showProfileSetup = false;
        if ($user->role === 'user' && !$user->runnerProfile) {
            $showProfileSetup = true;
        }

        // Get IDs of events the user is already registered for
        $registeredEventIds = \App\Models\EventRegistration::where('user_id', $user->id)
            ->where('status', 'registered')
            ->pluck('event_id')
            ->toArray();

        // Intelligent Event Recommendation (Hybrid Algorithm Engine)
        // Replaces standard chronological fetching with personalized sorting.
        $events = \App\Services\RecommendationService::getRecommendations($user, $registeredEventIds);

        // Fetch detailed registrations for the "My Events" tab (exclude completed)
        $myRegistrations = \App\Models\EventRegistration::with(['event.organizer'])
            ->withCount([
                'event as slots_left' => function ($q) {
                    // We don't necessarily need to compute slots left for already registered
                }
            ])
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->whereHas('event', function ($q) {
                $q->where('status', '!=', 'completed');
            })
            ->latest()
            ->get();

        // Fetch finished/completed event registrations for "Event History"
        $finishedRegistrations = \App\Models\EventRegistration::with(['event.organizer'])
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->whereHas('event', function ($q) {
                $q->where('status', 'completed');
            })
            ->latest()
            ->get();

        // Challenge data
        $challenge = \App\Models\UserChallenge::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('logs')
            ->first();

        // Auto-fail expired challenges
        if ($challenge && $challenge->is_expired) {
            $challenge->status = 'failed';
            $challenge->save();

            $config = \App\Models\UserChallenge::LEVELS[$challenge->level];
            $failLevel = $config['on_failure'];
            if ($user->runnerProfile) {
                $user->runnerProfile->update(['fitness_level' => $failLevel]);
            }
            $challenge = null; // No longer active
        }

        // Check for recently completed/failed challenge (for modal)
        $lastChallenge = \App\Models\UserChallenge::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'failed'])
            ->latest()
            ->first();

        $challengeLevels = \App\Models\UserChallenge::LEVELS;

        return view('dashboard.user.index', compact(
            'user',
            'showProfileSetup',
            'events',
            'registeredEventIds',
            'myRegistrations',
            'finishedRegistrations',
            'challenge',
            'lastChallenge',
            'challengeLevels'
        ));
    }

    public function profile()
    {
        return view('dashboard.profile', ['user' => Auth::user()]);
    }

    public function setupProfile(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'organizer') {
            $request->validate([
                'organization_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string',
            ]);

            $user->organizerProfile()->create([
                'organization_name' => $request->organization_name,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'verification_status' => 'pending',
            ]);

            return redirect()->route('dashboard')->with('success', 'Organizer profile setup complete!');
        }

        // Runner Validation — fitness_level is now auto-calculated
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'age' => 'required|integer|min:10|max:120',
            'gender' => 'required|string|in:male,female,other',
            'pace_minutes' => 'required|numeric|min:2|max:30',
            'pace_km' => 'required|numeric|min:1',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string',
            'health_conditions' => 'nullable|array',
            'other_condition_text' => 'nullable|string|max:255',
        ]);

        $conditions = $request->health_conditions ?? [];

        // Handle "Other" specification
        if (in_array('Other', $conditions) && $request->other_condition_text) {
            $conditions = array_diff($conditions, ['Other']);
            $conditions[] = 'Other: ' . $request->other_condition_text;
        }

        // Calculate average pace per km (Minutes / Km)
        $recentPace = null;
        if ($request->has('pace_minutes') && $request->has('pace_km')) {
            $recentPace = (float) $request->pace_minutes / (float) $request->pace_km;
        }

        // Auto-calculate fitness level using the Pace Calculation Engine
        $fitnessResult = \App\Services\FitnessLevelCalculator::calculate(
            (int) $request->age,
            $request->gender,
            $conditions,
            $recentPace
        );

        $user->runnerProfile()->create([
            'fitness_level' => $fitnessResult['level'],
            'verified_pace' => $fitnessResult['estimated_pace'],
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'name_extension' => $request->name_extension,
            'age' => $request->age,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'health_conditions' => $conditions,
        ]);

        $levelLabel = ucfirst($fitnessResult['level']);
        return redirect()->route('dashboard')->with('success', "Profile setup complete! You've been assigned as: {$levelLabel} runner (based on your age, gender & health data). Your Daily Challenge will verify and refine this.");
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'organizer') {
            $request->validate([
                'organization_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string',
            ]);

            $user->organizerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_name' => $request->organization_name,
                    'phone_number' => $request->phone_number,
                    'address' => $request->address,
                ]
            );

            return redirect()->route('dashboard.profile')->with('success', 'Organizer profile updated successfully!');
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'age' => 'required|integer|min:10|max:120',
            'gender' => 'required|string|in:male,female,other',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string',
            'health_conditions' => 'nullable|array',
            'other_condition_text' => 'nullable|string|max:255',
        ]);

        $conditions = $request->health_conditions ?? [];

        if (in_array('Other', $conditions) && $request->other_condition_text) {
            $conditions = array_diff($conditions, ['Other']);
            $conditions[] = 'Other: ' . $request->other_condition_text;
        }

        $user->runnerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'name_extension' => $request->name_extension,
                'age' => $request->age,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'health_conditions' => $conditions,
            ]
        );

        return redirect()->route('dashboard.profile')->with('success', 'Profile updated successfully!');
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        Auth::logout();

        if ($user->delete()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->with('success', 'Your account has been deleted successfully.');
        }

        return back()->with('error', 'Failed to delete account.');
    }

    public function settings()
    {
        return view('dashboard.settings', ['user' => Auth::user()]);
    }

    public function deleteUser(\App\Models\User $user)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $username = $user->username;
        $user->delete();

        return back()->with('success', "User ({$username}) deleted successfully.");
    }
}

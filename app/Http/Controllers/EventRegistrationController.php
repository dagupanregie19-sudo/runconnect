<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventRegistrationController extends Controller
{
    private const EVENT_TIMEZONE = 'Asia/Manila';

    public function register(Request $request, Event $event)
    {
        $user = Auth::user();

        // Only upcoming events are open for registration.
        if ($event->status !== 'upcoming') {
            return back()->with('error', 'This event is no longer open for registration.');
        }

        // Check registration period
        $now = Carbon::now(self::EVENT_TIMEZONE);
        $start = Carbon::parse($event->registration_start, self::EVENT_TIMEZONE)->startOfDay();
        $end = Carbon::parse($event->registration_end, self::EVENT_TIMEZONE)->endOfDay();

        if ($now->lt($start) || $now->gt($end)) {
            return back()->with('error', 'Registration is not open for this event.');
        }

        $fee = (float) $event->registration_fee;

        // Validate input
        $request->validate([
            'tshirt_size' => 'required|string|in:XS,S,M,L,XL,2XL,3XL',
            'payment_method' => $fee > 0 ? 'required|string' : 'nullable',
        ]);

        // --- Weighted Matching Algorithm (Priority Scoring) ---
        $priorityScore = 50; // Base score for early registration intent

        if ($user->runnerProfile) {
            // Category Match (Bonus +20)
            if (ucfirst($user->runnerProfile->fitness_level) === $event->difficulty) {
                $priorityScore += 20;
            }

            // Health Verification Limit (Bonus +10)
            if (empty($user->runnerProfile->health_conditions)) {
                $priorityScore += 10;
            }
        }

        // Determine if Event is in High Demand (< 10% slots left) to trigger Priority Queueing
        $registration = DB::transaction(function () use ($event, $user, $fee, $request, $priorityScore) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->status !== 'upcoming') {
                return ['error' => 'This event is no longer open for registration.'];
            }

            $now = Carbon::now(self::EVENT_TIMEZONE);
            $start = Carbon::parse($lockedEvent->registration_start, self::EVENT_TIMEZONE)->startOfDay();
            $end = Carbon::parse($lockedEvent->registration_end, self::EVENT_TIMEZONE)->endOfDay();
            if ($now->lt($start) || $now->gt($end)) {
                return ['error' => 'Registration is not open for this event.'];
            }

            $existing = EventRegistration::where('user_id', $user->id)
                ->where('event_id', $lockedEvent->id)
                ->whereIn('status', [
                    EventRegistration::STATUS_REGISTERED,
                    EventRegistration::STATUS_PENDING_ALLOCATION,
                ])
                ->first();

            if ($existing) {
                return ['error' => 'You already have an active registration for this event.'];
            }

            $registeredCount = EventRegistration::where('event_id', $lockedEvent->id)
                ->where('status', EventRegistration::STATUS_REGISTERED)
                ->count();

            $slotsLeft = max(0, ((int) $lockedEvent->slots) - $registeredCount);
            if ($slotsLeft <= 0) {
                return ['error' => 'Sorry, this event is fully booked.'];
            }

            $isHighDemand = ($lockedEvent->slots > 0 && ($slotsLeft / $lockedEvent->slots) <= 0.1);
            $finalStatus = $isHighDemand
                ? EventRegistration::STATUS_PENDING_ALLOCATION
                : EventRegistration::STATUS_REGISTERED;

            $attrs = [
                'status' => $finalStatus,
                'payment_status' => $fee > 0 ? 'paid' : 'free',
                'payment_method' => $fee > 0 ? $request->payment_method : null,
                'amount_paid' => $fee > 0 ? $fee : 0,
                'reference_number' => $fee > 0 ? ('RC-' . strtoupper(Str::random(8))) : null,
                'tshirt_size' => $request->tshirt_size,
                'priority_score' => $priorityScore,
            ];

            $created = EventRegistration::updateOrCreate(
                ['user_id' => $user->id, 'event_id' => $lockedEvent->id],
                $attrs
            );

            return ['registration' => $created, 'isHighDemand' => $isHighDemand];
        });

        if (isset($registration['error'])) {
            return back()->with('error', $registration['error']);
        }

        $created = $registration['registration'];
        $isHighDemand = $registration['isHighDemand'];

        if ($fee > 0) {
            $msg = $isHighDemand
                ? "Payment confirmed! Event is near capacity. You are placed in the Priority Queue. Reference: {$created->reference_number}"
                : "Registered successfully! Payment of ₱" . number_format($fee, 2) . " confirmed. Reference: {$created->reference_number}";
        } else {
            $msg = $isHighDemand
                ? "Event is near capacity! You have been placed in the Priority Queue based on your runner matching score."
                : "You have been registered successfully!";
        }

        return back()->with('success', $msg);
    }

    public function cancel(Event $event)
    {
        $user = Auth::user();

        $registration = EventRegistration::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('status', EventRegistration::STATUS_REGISTERED)
            ->first();

        if (!$registration) {
            return back()->with('error', 'Registration not found.');
        }

        $registration->update(['status' => EventRegistration::STATUS_CANCELLED]);

        return back()->with('success', 'Your registration has been cancelled.');
    }
}

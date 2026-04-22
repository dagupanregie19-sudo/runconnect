<?php

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('slots:allocate {event_id?}', function (?int $eventId = null) {
    $eventIds = $eventId
        ? collect([$eventId])
        : EventRegistration::where('status', EventRegistration::STATUS_PENDING_ALLOCATION)
            ->distinct()
            ->pluck('event_id');

    if ($eventIds->isEmpty()) {
        $this->info('No pending allocations to process.');
        return;
    }

    foreach ($eventIds as $id) {
        DB::transaction(function () use ($id) {
            $event = Event::whereKey($id)->lockForUpdate()->first();
            if (!$event) {
                return;
            }

            $registered = EventRegistration::where('event_id', $event->id)
                ->where('status', EventRegistration::STATUS_REGISTERED)
                ->count();

            $remaining = max(0, ((int) $event->slots) - $registered);
            if ($remaining <= 0) {
                EventRegistration::where('event_id', $event->id)
                    ->where('status', EventRegistration::STATUS_PENDING_ALLOCATION)
                    ->update(['status' => EventRegistration::STATUS_CANCELLED]);
                return;
            }

            $pending = EventRegistration::where('event_id', $event->id)
                ->where('status', EventRegistration::STATUS_PENDING_ALLOCATION)
                ->orderByDesc('priority_score')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $acceptedIds = $pending->take($remaining)->pluck('id');
            $rejectedIds = $pending->slice($remaining)->pluck('id');

            if ($acceptedIds->isNotEmpty()) {
                EventRegistration::whereIn('id', $acceptedIds)
                    ->update(['status' => EventRegistration::STATUS_REGISTERED]);
            }

            if ($rejectedIds->isNotEmpty()) {
                EventRegistration::whereIn('id', $rejectedIds)
                    ->update(['status' => EventRegistration::STATUS_CANCELLED]);
            }
        });

        $this->info("Processed pending allocations for event #{$id}.");
    }
})->purpose('Process pending slot allocations by priority score');

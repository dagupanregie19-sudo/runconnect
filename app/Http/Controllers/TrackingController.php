<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrackingController extends Controller
{
    /**
     * Runner updates their live location.
     */
    public function updateLocation(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'distance' => 'nullable|numeric',
            'live_route_data' => 'nullable|string',
        ]);

        $registration = EventRegistration::with('event')
            ->where('user_id', $user->id)
            ->where('event_id', $request->event_id)
            ->where('status', 'registered')
            ->first();

        if ($registration) {
            $event = $registration->event;
            if ($event && in_array($event->status, ['completed', 'ended'])) {
                return response()->json(['status' => 'event_ended']);
            }

            $offRoute = false;
            if ($event && $event->route_data) {
                $offRoute = $this->isOffRoute($event->route_data, (float) $request->lat, (float) $request->lng);
            }

            $registration->update([
                'current_lat' => $request->lat,
                'current_lng' => $request->lng,
                'current_distance' => $request->distance ?? 0,
                'live_route_data' => $request->live_route_data,
                'last_tracked_at' => now(),
                'is_off_route' => $offRoute,
                'off_route_since' => $offRoute
                    ? ($registration->off_route_since ?? now())
                    : null,
                'off_route_count' => $offRoute
                    ? ((int) $registration->off_route_count + ($registration->is_off_route ? 0 : 1))
                    : (int) $registration->off_route_count,
            ]);

            return response()->json([
                'status' => 'success',
                'event_status' => $event ? $event->status : null,
                'is_off_route' => $offRoute,
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Not registered for this event'], 403);
    }

    /**
     * Organizer fetches live runner locations for an event.
     */
    public function getLocations($event_id)
    {
        $user = Auth::user();

        $event = Event::findOrFail($event_id);
        $isOrganizerOwner = $event->organizer_id === $user->id;
        $isAdmin = $user->role === 'admin';
        $isRegisteredRunner = EventRegistration::where('event_id', $event_id)
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->exists();

        if (!($isOrganizerOwner || $isAdmin || $isRegisteredRunner)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $runners = collect();
        if ($isOrganizerOwner || $isAdmin) {
            // Fetch runners who have checked in within the last 15 minutes to avoid stale markers
            $runners = EventRegistration::with(['user.runnerProfile'])
                ->where('event_id', $event_id)
                ->where('status', 'registered')
                ->whereNotNull('current_lat')
                ->whereNotNull('current_lng')
                ->where('last_tracked_at', '>=', now()->subMinutes(15))
                ->orderByDesc('current_distance')
                ->get()
                ->map(function ($reg) {
                    $name = $reg->user->runnerProfile
                        ? $reg->user->runnerProfile->first_name . ' ' . $reg->user->runnerProfile->last_name
                        : $reg->user->username;

                    return [
                        'id' => $reg->user_id,
                        'name' => $name,
                        'lat' => $reg->current_lat,
                        'lng' => $reg->current_lng,
                        'distance' => $reg->current_distance,
                        'last_tracked_at' => $reg->last_tracked_at->diffForHumans(),
                        'live_route_data' => $reg->live_route_data ? json_decode($reg->live_route_data) : null,
                        'has_emergency' => $reg->has_emergency,
                        'is_off_route' => (bool) $reg->is_off_route,
                        'off_route_since' => $reg->off_route_since ? $reg->off_route_since->diffForHumans() : null,
                        'off_route_count' => (int) $reg->off_route_count,
                    ];
                });
        }

        $routeGeoJson = null;
        if ($event->route_data && Storage::disk('public')->exists($event->route_data)) {
            $decoded = json_decode(Storage::disk('public')->get($event->route_data), true);
            if (is_array($decoded)) {
                $routeGeoJson = $decoded;
            }
        }

        return response()->json([
            'status' => 'success',
            'runners' => $runners,
            'route_data' => $event->route_data,
            'route_geojson' => $routeGeoJson,
        ]);
    }

    private function isOffRoute(string $routeDataPath, float $lat, float $lng): bool
    {
        if (!Storage::disk('public')->exists($routeDataPath)) {
            return false;
        }

        $geoJsonRaw = Storage::disk('public')->get($routeDataPath);
        $geoJson = json_decode($geoJsonRaw, true);
        if (!is_array($geoJson)) {
            return false;
        }

        $lineCoordinates = $this->extractRoutePoints($geoJson);
        if (count($lineCoordinates) < 2) {
            return false;
        }

        // 0.08 km (~80m) threshold for off-route detection.
        $thresholdKm = 0.08;
        $nearestDistanceKm = $this->minimumDistanceToRouteKm([$lat, $lng], $lineCoordinates);

        return $nearestDistanceKm > $thresholdKm;
    }

    private function extractRoutePoints(array $geoJson): array
    {
        $features = $geoJson['features'] ?? [];
        $points = [];

        foreach ($features as $feature) {
            $geometry = $feature['geometry'] ?? null;
            if (!is_array($geometry)) {
                continue;
            }

            $type = $geometry['type'] ?? null;
            $coordinates = $geometry['coordinates'] ?? [];

            if ($type === 'LineString' && is_array($coordinates)) {
                foreach ($coordinates as $coord) {
                    if (is_array($coord) && count($coord) >= 2) {
                        $points[] = [(float) $coord[1], (float) $coord[0]];
                    }
                }
            }

            if ($type === 'MultiLineString' && is_array($coordinates)) {
                foreach ($coordinates as $line) {
                    if (!is_array($line)) {
                        continue;
                    }
                    foreach ($line as $coord) {
                        if (is_array($coord) && count($coord) >= 2) {
                            $points[] = [(float) $coord[1], (float) $coord[0]];
                        }
                    }
                }
            }
        }

        return $points;
    }

    private function minimumDistanceToRouteKm(array $point, array $routePoints): float
    {
        $min = INF;
        for ($i = 0; $i < count($routePoints) - 1; $i++) {
            $distance = $this->pointToSegmentDistanceKm($point, $routePoints[$i], $routePoints[$i + 1]);
            $min = min($min, $distance);
        }

        return $min === INF ? 0.0 : $min;
    }

    private function pointToSegmentDistanceKm(array $p, array $a, array $b): float
    {
        // Equirectangular projection around current latitude for lightweight geofence checks.
        $latRef = deg2rad(($a[0] + $b[0] + $p[0]) / 3);
        $earthRadiusKm = 6371.0;

        $ax = deg2rad($a[1]) * cos($latRef) * $earthRadiusKm;
        $ay = deg2rad($a[0]) * $earthRadiusKm;
        $bx = deg2rad($b[1]) * cos($latRef) * $earthRadiusKm;
        $by = deg2rad($b[0]) * $earthRadiusKm;
        $px = deg2rad($p[1]) * cos($latRef) * $earthRadiusKm;
        $py = deg2rad($p[0]) * $earthRadiusKm;

        $abx = $bx - $ax;
        $aby = $by - $ay;
        $abLenSq = ($abx * $abx) + ($aby * $aby);

        if ($abLenSq <= 0.0) {
            return sqrt((($px - $ax) ** 2) + (($py - $ay) ** 2));
        }

        $t = ((($px - $ax) * $abx) + (($py - $ay) * $aby)) / $abLenSq;
        $t = max(0.0, min(1.0, $t));

        $projX = $ax + ($t * $abx);
        $projY = $ay + ($t * $aby);

        return sqrt((($px - $projX) ** 2) + (($py - $projY) ** 2));
    }

    public function reportEmergency(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $registration = EventRegistration::where('user_id', $user->id)
            ->where('event_id', $request->event_id)
            ->where('status', 'registered')
            ->first();

        if ($registration) {
            $registration->update(['has_emergency' => true]);
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'Not registered'], 403);
    }

    public function resolveEmergency($event_id, $user_id)
    {
        $actor = Auth::user();
        $event = Event::findOrFail($event_id);

        $isOrganizerOwner = $event->organizer_id === $actor->id;
        $isAdmin = $actor->role === 'admin';

        if (!($isOrganizerOwner || $isAdmin)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $registration = EventRegistration::where('event_id', $event_id)
            ->where('user_id', $user_id)
            ->first();

        if ($registration) {
            $registration->update(['has_emergency' => false]);
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error'], 404);
    }
}

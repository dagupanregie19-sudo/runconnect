<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'difficulty' => 'required|string|in:Beginner,Improving,Intermediate',
            'distance' => 'required|numeric|min:0',
            'registration_start' => 'required|date',
            'registration_end' => 'required|date|after_or_equal:registration_start',
            'slots' => 'required|integer|min:1',
            'location' => 'required|string',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'registration_fee' => 'required|numeric|min:0',
        ]);

        $data = $request->except(['manual_route_data']);

        // Handle Map-Pinned Route (GeoJSON string from OSRM)
        if ($request->manual_route_data) {
            $filename = 'routes/generated_' . time() . '_' . Str::random(10) . '.geojson';
            Storage::disk('public')->put($filename, $request->manual_route_data);
            $data['route_data'] = $filename;
        }

        Auth::user()->events()->create($data);

        return redirect()->route('dashboard')->with('success', 'Event created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        // Allow organizer or admin
        if ($event->organizer_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($event->status === 'completed') {
            return redirect()->route('dashboard')->with('error', 'Completed events cannot be edited (view-only).');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'difficulty' => 'required|string|in:Beginner,Improving,Intermediate',
            'distance' => 'required|numeric|min:0',
            'registration_start' => 'required|date',
            'registration_end' => 'required|date|after_or_equal:registration_start',
        ]);

        $data = $request->except(['manual_route_data']);

        // Handle Map-Pinned Route (GeoJSON string from OSRM)
        if ($request->manual_route_data) {
            $filename = 'routes/generated_' . time() . '_' . Str::random(10) . '.geojson';
            Storage::disk('public')->put($filename, $request->manual_route_data);
            $data['route_data'] = $filename;
        }

        $event->update($data);

        return redirect()->route('dashboard')->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        if ($event->organizer_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($event->status === 'completed') {
            return redirect()->route('dashboard')->with('error', 'Completed events cannot be deleted.');
        }

        $event->delete();

        return redirect()->route('dashboard')->with('success', 'Event deleted successfully.');
    }

    /**
     * Start or Stop the Event
     */
    public function toggleStatus(Event $event)
    {
        if ($event->organizer_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($event->status === 'upcoming') {
            $event->update(['status' => 'started']);
            return redirect()->back()->with('success', 'Event started successfully. Runners can now join and their locations will be tracked!');
        } elseif ($event->status === 'started') {
            $event->update(['status' => 'completed']);
            return redirect()->back()->with('success', 'Event marked as completed.');
        }

        return redirect()->back();
    }
}

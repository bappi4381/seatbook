<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreEventRequest;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Models\Event;
use App\Services\SeatGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->where('organizer_id', auth()->id())
            ->withCount([
                'seats',
                'seats as booked_seats_count' => fn ($query) => $query->where('status', 'booked'),
                'seats as available_seats_count' => fn ($query) => $query->where('status', '!=', 'booked'),
            ])
            ->latest()
            ->paginate(10);

        return view('organizer.events.index', compact('events'));
    }

    public function create(): View
    {
        Gate::authorize('create', Event::class);

        return view('organizer.events.create');
    }

    public function store(StoreEventRequest $request, SeatGeneratorService $seatGenerator): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $data = $request->validated();

        DB::transaction(function () use ($data, $seatGenerator) {
            $event = Event::create([
                'organizer_id' => auth()->id(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'venue' => $data['venue'],
                'starts_at' => $data['starts_at'],
                'status' => 'published',
            ]);

            $seatGenerator->generate(
                $event,
                (int) $data['rows'],
                (int) $data['seats_per_row'],
                $data['price'],
            );
        });

        return redirect()
            ->route('organizer.events.index')
            ->with('status', 'Event created successfully.');
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        $event->loadCount([
            'seats',
            'seats as booked_seats_count' => fn ($query) => $query->where('status', 'booked'),
            'seats as available_seats_count' => fn ($query) => $query->where('status', '!=', 'booked'),
        ]);
        $event->load([
            'bookings' => fn ($query) => $query->with(['user', 'seats'])->latest(),
        ]);

        return view('organizer.events.edit', compact('event'));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $data = $request->validated();

        DB::transaction(function () use ($event, $data) {
            $event->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'venue' => $data['venue'],
                'starts_at' => $data['starts_at'],
                'status' => $data['status'],
            ]);

            // Price applies only to seats with no active hold and no booking;
            // seats already booked/held keep their existing price.
            $event->seats()
                ->whereDoesntHave('hold', fn ($query) => $query->where('expires_at', '>', now()))
                ->whereDoesntHave('bookings')
                ->update(['price' => $data['price']]);
        });

        return redirect()
            ->route('organizer.events.index')
            ->with('status', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        if ($event->bookings()->where('status', 'confirmed')->exists()) {
            return redirect()
                ->route('organizer.events.index')
                ->with('error', 'This event cannot be deleted because it has confirmed bookings.');
        }

        $event->delete();

        return redirect()
            ->route('organizer.events.index')
            ->with('status', 'Event deleted successfully.');
    }
}

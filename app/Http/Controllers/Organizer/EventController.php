<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\DestroyEventRequest;
use App\Http\Requests\Organizer\StoreEventRequest;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    public function store(StoreEventRequest $request, EventService $eventService): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $eventService->create($request->user(), $request->validated());

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

    public function update(UpdateEventRequest $request, Event $event, EventService $eventService): RedirectResponse
    {
        Gate::authorize('update', $event);

        $eventService->update($event, $request->validated());

        return redirect()
            ->route('organizer.events.index')
            ->with('status', 'Event updated successfully.');
    }

    public function destroy(DestroyEventRequest $request, Event $event, EventService $eventService): RedirectResponse
    {
        Gate::authorize('delete', $event);

        try {
            $eventService->delete($event);
        } catch (HttpException $exception) {
            return redirect()
                ->route('organizer.events.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('organizer.events.index')
            ->with('status', 'Event deleted successfully.');
    }
}

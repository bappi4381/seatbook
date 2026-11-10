<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\EventSeatCache;
use App\Support\SeatState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->where('status', 'published')
            ->where('starts_at', '>', now())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('venue', 'like', "%{$search}%");
                });
            })
            ->withCount([
                'seats',
                'seats as available_seats_count' => fn ($query) => $query->where('status', '!=', 'booked'),
            ])
            ->withMin('seats', 'price')
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('events.index', compact('events', 'search'));
    }

    public function show(Event $event): View
    {
        Gate::authorize('view', $event);

        $event->load([
            'seats' => fn ($query) => $query->orderBy('row')->orderBy('number'),
            'seats.hold',
        ]);

        $user = auth()->user();
        $seats = $event->seats->map(fn ($seat) => SeatState::payload($seat, $user))->values();
        $rows = $event->seats->groupBy('row')->map(function ($rowSeats) use ($user) {
            return $rowSeats->sortBy('number')->values()->map(fn ($seat) => SeatState::payload($seat, $user));
        });

        $myHoldExpiresAt = $event->seats
            ->map(fn ($seat) => SeatState::payload($seat, $user))
            ->filter(fn (array $seat) => $seat['state'] === 'held_by_me' && $seat['expires_at'])
            ->min('expires_at');

        return view('events.show', [
            'event' => $event,
            'rows' => $rows,
            'seatsJson' => $seats,
            'myHoldExpiresAt' => $myHoldExpiresAt,
            'serverNow' => now()->toIso8601String(),
        ]);
    }

    public function seats(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $shared = Cache::remember(EventSeatCache::key($event->id), 2, function () use ($event) {
            $event->load([
                'seats' => fn ($query) => $query->orderBy('row')->orderBy('number'),
                'seats.hold',
            ]);

            return $event->seats
                ->map(fn ($seat) => SeatState::sharedPayload($seat))
                ->values()
                ->all();
        });

        $user = auth()->user();

        return response()->json([
            'server_now' => now()->toIso8601String(),
            'seats' => collect($shared)
                ->map(fn (array $seat) => SeatState::personalize($seat, $user))
                ->values(),
        ]);
    }
}

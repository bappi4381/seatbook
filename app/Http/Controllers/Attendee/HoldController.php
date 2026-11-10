<?php

namespace App\Http\Controllers\Attendee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendee\ReleaseHoldRequest;
use App\Http\Requests\Attendee\StoreHoldRequest;
use App\Models\Event;
use App\Services\SeatHoldService;
use App\Support\SeatState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HoldController extends Controller
{
    public function store(StoreHoldRequest $request, Event $event, SeatHoldService $seatHoldService): JsonResponse|RedirectResponse
    {
        try {
            $holds = $seatHoldService->hold(
                $event,
                $request->user(),
                $request->validated('seat_ids'),
            );
        } catch (HttpException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], $exception->getStatusCode());
            }

            return redirect()
                ->route('events.show', $event)
                ->with('error', $exception->getMessage());
        }

        $expiresAt = $holds->sortBy('expires_at')->first()?->expires_at;
        $holds->load(['seat.hold']);

        $payload = [
            'message' => 'Seats held successfully.',
            'expires_at' => $expiresAt?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'holds' => $holds->map(fn ($hold) => SeatState::payload($hold->seat, $request->user()))->values(),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, 201);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('status', $payload['message']);
    }

    public function destroy(ReleaseHoldRequest $request, Event $event, SeatHoldService $seatHoldService): JsonResponse|RedirectResponse
    {
        $count = $seatHoldService->releaseForUser($event, $request->user());

        $payload = [
            'message' => $count > 0 ? 'Your holds were released.' : 'No active holds to release.',
            'released' => $count,
            'server_now' => now()->toIso8601String(),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('status', $payload['message']);
    }
}

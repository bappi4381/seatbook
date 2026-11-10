<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $events = Event::query()
            ->where('status', 'published')
            ->where('starts_at', '>', now())
            ->withCount([
                'seats',
                'seats as available_seats_count' => fn ($query) => $query->where('status', '!=', 'booked'),
            ])
            ->withMin('seats', 'price')
            ->orderBy('starts_at')
            ->take(6)
            ->get();

        return view('welcome', compact('events'));
    }
}

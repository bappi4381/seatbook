<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isOrganizer()) {
            return redirect()->route('organizer.events.index');
        }

        return redirect()->route('events.index');
    }
}

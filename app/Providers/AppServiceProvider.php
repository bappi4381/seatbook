<?php

namespace App\Providers;

use App\Models\Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('seat-hold', function (Request $request) {
            $event = $request->route('event');
            $eventId = $event instanceof Event ? $event->id : (string) $event;

            return Limit::perMinute(10)
                ->by(($request->user()?->id ?? $request->ip()).'|'.$eventId)
                ->response(function (Request $request, array $headers) {
                    $message = 'Too many hold attempts. Please wait a moment and try again.';

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    return redirect()
                        ->back()
                        ->with('error', $message);
                });
        });
    }
}

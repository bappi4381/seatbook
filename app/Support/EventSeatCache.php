<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class EventSeatCache
{
    public static function key(int $eventId): string
    {
        return "events.{$eventId}.seats";
    }

    public static function forget(int $eventId): void
    {
        Cache::forget(self::key($eventId));
    }
}

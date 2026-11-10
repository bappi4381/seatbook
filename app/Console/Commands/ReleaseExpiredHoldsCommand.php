<?php

namespace App\Console\Commands;

use App\Services\SeatHoldService;
use Illuminate\Console\Command;

class ReleaseExpiredHoldsCommand extends Command
{
    protected $signature = 'holds:release-expired';

    protected $description = 'Delete expired seat holds and mark those seats available';

    public function handle(SeatHoldService $seatHoldService): int
    {
        $count = $seatHoldService->releaseExpired();

        $this->info("Released {$count} expired hold(s).");

        return self::SUCCESS;
    }
}

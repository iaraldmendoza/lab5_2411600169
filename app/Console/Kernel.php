<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [\App\Console\Commands\CheckReorderPoints::class];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inventory:check-reorders')
            ->dailyAt('08:00')
            ->timezone('Asia/Manila')
            ->withoutOverlapping(30)
            ->appendOutputTo(storage_path('logs/reorder-checks.log'))
            ->onFailure(fn () => Log::error('The daily inventory reorder task returned a failure status.'));
    }
}

<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Scheduled analyses
        // $schedule->command('analyses:run-scheduled')->hourly();

        // Clean up old snapshots from MinIO
        // $schedule->command('snapshots:cleanup')->daily();

        // Reset monthly analysis counts
        // $schedule->command('subscriptions:reset-counts')->monthlyOn(1);
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

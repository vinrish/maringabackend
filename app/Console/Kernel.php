<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\CheckRecurringObligations::class,
        Commands\GenerateObligationsForClients::class,
        Commands\UpdateObligationsWithServices::class,
        Commands\DetachTodayAddedObligationServices::class,
        Commands\GenerateTasksForObligations::class,
        Commands\RemoveDuplicateServicesFromObligations::class,
        Commands\RemoveDuplicateServicesFromAllObligations::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('obligations:check-recurring')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

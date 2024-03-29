<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Kirby\Company\UI\CLI\SyncHolidaysCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('telescope:prune')->monthly();
        $schedule->command(SyncHolidaysCommand::class, ['--next-year' => true])->cron('0 0 1 */12 *');

        if ($this->app->environment('production')) {
            $schedule->command('backup:run')->hourly();
            $schedule->command('backup:clean')->weekly();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Initialize\ImageFilePreviews::class,
        Commands\Initialize\FileVersions::class,
        Commands\Initialize\TeamS3Secrets::class,
        Commands\Index\Create::class,
        Commands\Index\Shareables::class,
        Commands\Index\Clear::class,
        Commands\Index\Delete::class,
        Commands\Clear\Trash::class,
        Commands\Clear\Versions::class,
        Commands\Sharing\Conflicts::class,
        Commands\Sharing\Owners::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('clear:trash --no-confirm')->daily();
        $schedule->command('clear:versions')->daily();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}

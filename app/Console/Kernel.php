<?php

namespace App\Console;

use App\Services\Order\OrderServices;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    //crontab -u www -e
    //* * * * * cd/home/www/mcshop && php artisan schedule:run >> /dev/null 2>&1
    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            OrderServices::getInstance()->autoConfirm();
        })->dailyAt('3:00')->runInBackground()->name('auto_confirm')->onOneServer();

        $schedule->call(function () {
            Log::info('test schedule');
        })->everyMinute()
            ->runInBackground()->name('log')->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

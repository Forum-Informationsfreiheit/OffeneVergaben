<?php

namespace App\Console;

use Carbon\Carbon;
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

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $scrapeAt = env('APP_SCHEDULE_SCRAPE_AT_TIMESTRING');
        if ($scrapeAt && $this->validateTimeString($scrapeAt)) {
            $schedule->command('fif:scrape')->at($scrapeAt);
        }

        $processAt = env('APP_SCHEDULE_PROCESS_AT_TIMESTRING');
        if ($processAt && $this->validateTimeString($processAt)) {
            $schedule->command('fif:process')->at($processAt);
        }
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

    protected function validateTimeString($timeString) {
        try {
            Carbon::parse($timeString);
            return true;
        } catch(\Exception $ex) {
            Log::error('App Schedule: Unable to parse time string parameter. Check .env settings, expected format HH:ii - H:24-hour format, i:minutes with leading zeros',['timeString' => $timeString]);
        }

        return false;
    }
}

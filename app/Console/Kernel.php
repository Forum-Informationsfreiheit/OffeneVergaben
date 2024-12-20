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
        /*
         * 20200406 - Scraper running now individually
         *
        $scrapeAt = env('APP_SCHEDULE_SCRAPE_AT_TIMESTRING');
        if ($scrapeAt && $this->validateTimeString($scrapeAt)) {
            $schedule->command('fif:scrape')->at($scrapeAt);
        }
        */

        // XML processing ----------------------------------------
        $processAt = env('APP_SCHEDULE_PROCESS_AT_TIMESTRING');
        if ($processAt && $this->validateTimeString($processAt)) {
            $schedule->command('fif:process')->dailyAt($processAt)
                // 2024-12-20 add organization stats calculation start right after processing has finished
                ->after(function() {
                        $this->artisan->call('fif:update-organization-stats');
                });
        }

        // Make CSV Dump
        $dumpAt = env('APP_SCHEDULE_DUMP_AT_TIMESTRING');
        if ($dumpAt && $this->validateTimeString($dumpAt)) {
            $schedule->command('fif:dump-datasets')->dailyAt($dumpAt);
        }

        // Send subscription update notifications
        $sendAt = env('APP_SCHEDULE_SEND_SUBSCRIPTION_SUMMARY_UPDATE_AT_TIMESTRING');
        if ($sendAt && $this->validateTimeString($sendAt)) {
            $schedule->command('fif:send-subscription-updates')->dailyAt($sendAt);
        }

        // SUBSCRIPTIONS & SUBSCRIBER clean up ---------------------------
        $schedule->command('fif:clean-up-subscriptions')->hourly();

        // TMP files clean up ---------------------------------------------
        $schedule->command('fif:clean-up-tmp-links')->everyThirtyMinutes();

        // Generate sitemap daily -----------------------------------------
        $schedule->command('fif:generate-sitemap')->dailyAt('03:00');
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

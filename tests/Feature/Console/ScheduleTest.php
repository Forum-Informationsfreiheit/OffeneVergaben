<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The schedule in App\Console\Kernel. The times of the daily jobs come from config('app.schedule.*'), set by the
 * APP_SCHEDULE_*_TIMESTRING environment variables.
 */
class ScheduleTest extends TestCase
{
    public function testSchedulesCommands()
    {
        $commands = $this->scheduledCommands([
            'process_at'                   => '02:15',
            'dump_at'                      => '04:30',
            'send_subscription_updates_at' => '07:00',
        ]);

        $this->assertSame([
            'fif:process'                   => '15 2 * * *',
            'fif:dump-datasets'             => '30 4 * * *',
            'fif:send-subscription-updates' => '0 7 * * *',
            'fif:clean-up-subscriptions'    => '0 * * * *',
            'fif:clean-up-tmp-links'        => '*/30 * * * *',
            'fif:generate-sitemap'          => '0 3 * * *',
        ], $commands);
    }

    public function testDailyJobsWithoutTimeAreNotScheduled()
    {
        $commands = $this->scheduledCommands([
            'process_at'                   => '',
            'dump_at'                      => '',
            'send_subscription_updates_at' => '',
        ]);

        $this->assertSame(['fif:clean-up-subscriptions', 'fif:clean-up-tmp-links', 'fif:generate-sitemap'], array_keys($commands));
    }

    public function testInvalidTimeIsLoggedAndNotScheduled()
    {
        Log::shouldReceive('error')->once();

        $commands = $this->scheduledCommands([
            'process_at'                   => 'irgendwann',
            'dump_at'                      => '04:30',
            'send_subscription_updates_at' => '',
        ]);

        $this->assertArrayNotHasKey('fif:process', $commands);
        $this->assertArrayHasKey('fif:dump-datasets', $commands);
    }

    /**
     * Builds the schedule with the given times (config app.schedule) and returns command => cron expression.
     */
    private function scheduledCommands(array $times)
    {
        config(['app.schedule' => $times]);

        $schedule = new Schedule();
        (function () use ($schedule) {
            $this->schedule($schedule);
        })->call($this->app->make(Kernel::class));

        $commands = [];
        foreach ($schedule->events() as $event) {
            preg_match("/'?artisan'? (\S+)/", $event->command, $match);
            $commands[$match[1]] = $event->expression;
        }

        return $commands;
    }
}

<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The schedule in App\Console\Kernel. The times of the daily jobs come from APP_SCHEDULE_*_TIMESTRING.
 */
class ScheduleTest extends TestCase
{
    /**
     * @var array ['_SERVER' => [...], '_ENV' => [...]] values before the test, null for variables that were not set
     */
    private $originalEnvironment = ['_SERVER' => [], '_ENV' => []];

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment['_SERVER'] as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        foreach ($this->originalEnvironment['_ENV'] as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function testSchedulesCommands()
    {
        $commands = $this->scheduledCommands([
            'APP_SCHEDULE_PROCESS_AT_TIMESTRING'                          => '02:15',
            'APP_SCHEDULE_DUMP_AT_TIMESTRING'                             => '04:30',
            'APP_SCHEDULE_SEND_SUBSCRIPTION_SUMMARY_UPDATE_AT_TIMESTRING' => '07:00',
        ]);

        $this->assertSame([
            'fif:process'                   => '15 2 * * *',
            'fif:dump-datasets'             => '30 4 * * *',
            'fif:send-subscription-updates' => '0 7 * * *',
            'fif:clean-up-subscriptions'    => '0 * * * *',
            'fif:clean-up-tmp-links'        => '0,30 * * * *',
            'fif:generate-sitemap'          => '0 3 * * *',
        ], $commands);
    }

    public function testDailyJobsWithoutTimeAreNotScheduled()
    {
        $commands = $this->scheduledCommands([
            'APP_SCHEDULE_PROCESS_AT_TIMESTRING'                          => '',
            'APP_SCHEDULE_DUMP_AT_TIMESTRING'                             => '',
            'APP_SCHEDULE_SEND_SUBSCRIPTION_SUMMARY_UPDATE_AT_TIMESTRING' => '',
        ]);

        $this->assertSame(['fif:clean-up-subscriptions', 'fif:clean-up-tmp-links', 'fif:generate-sitemap'], array_keys($commands));
    }

    public function testInvalidTimeIsLoggedAndNotScheduled()
    {
        Log::shouldReceive('error')->once();

        $commands = $this->scheduledCommands([
            'APP_SCHEDULE_PROCESS_AT_TIMESTRING'                          => 'irgendwann',
            'APP_SCHEDULE_DUMP_AT_TIMESTRING'                             => '04:30',
            'APP_SCHEDULE_SEND_SUBSCRIPTION_SUMMARY_UPDATE_AT_TIMESTRING' => '',
        ]);

        $this->assertArrayNotHasKey('fif:process', $commands);
        $this->assertArrayHasKey('fif:dump-datasets', $commands);
    }

    /**
     * Builds the schedule with the given environment variables and returns command => cron expression.
     */
    private function scheduledCommands(array $environment)
    {
        foreach ($environment as $key => $value) {
            if (!array_key_exists($key, $this->originalEnvironment['_ENV'])) {
                $this->originalEnvironment['_SERVER'][$key] = $_SERVER[$key] ?? null;
                $this->originalEnvironment['_ENV'][$key] = $_ENV[$key] ?? null;
            }
            // env() reads $_SERVER first, then $_ENV (phpdotenv 5); .env fills both
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
        }

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

<?php

namespace Tests\Feature\Console;

use App\Jobs\CleanUpStaleTemporaryDownloadLinksJob;
use App\Jobs\GenerateSitemapJob;
use App\Jobs\MakeDatasetsCsvDumpJob;
use App\Jobs\Process;
use App\Jobs\SendSubscriptionUpdateNotificationsJob;
use App\Jobs\UpdateOrganizationStatsJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Jobs\JobTestCase;

/**
 * The fif:* commands start the jobs. The jobs are faked here, they are tested in tests/Feature/Jobs.
 */
class CommandsTest extends JobTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /**
     * @dataProvider jobCommandProvider
     */
    public function testCommandStartsJob($command, $job)
    {
        $this->artisan($command)->assertExitCode(0)->run();

        Bus::assertDispatched($job);
    }

    public function jobCommandProvider()
    {
        return [
            'fif:dump-datasets'             => ['fif:dump-datasets', MakeDatasetsCsvDumpJob::class],
            'fif:generate-sitemap'          => ['fif:generate-sitemap', GenerateSitemapJob::class],
            'fif:send-subscription-updates' => ['fif:send-subscription-updates', SendSubscriptionUpdateNotificationsJob::class],
            'fif:clean-up-tmp-links'        => ['fif:clean-up-tmp-links', CleanUpStaleTemporaryDownloadLinksJob::class],
        ];
    }

    public function testProcessStartsJobForGivenKerndatenIds()
    {
        $this->artisan('fif:process', ['--kerndaten_id' => '12, 34'])->assertExitCode(0)->run();

        Bus::assertDispatched(Process::class, function ($job) {
            return $this->jobProperty($job, 'recordIds') === ['12', '34'];
        });
    }

    /**
     * Without --kerndaten_id all records of the scraper database that were not processed yet. The test rolls back its
     * own transaction on that connection.
     */
    public function testProcessStartsJobForUnprocessedKerndaten()
    {
        $scraper = DB::connection('mysql_scraper');
        $scraper->beginTransaction();

        try {
            $unprocessed = $this->insertKerndaten([]);
            $processed   = $this->insertKerndaten(['app_processed_at' => now(), 'app_dataset_id' => 1]);

            $this->artisan('fif:process')->assertExitCode(0)->run();

            Bus::assertDispatched(Process::class, function ($job) use ($unprocessed, $processed) {
                $ids = $this->jobProperty($job, 'recordIds');

                return in_array($unprocessed, $ids) && !in_array($processed, $ids);
            });
        } finally {
            $scraper->rollBack();
        }
    }

    public function testUpdateOrganizationStatsStartsJob()
    {
        $organization = $this->createOrganization();

        $this->artisan('fif:update-organization-stats')->assertExitCode(0)->run();
        $this->artisan('fif:update-organization-stats', ['--organization' => $organization->id])->assertExitCode(0)->run();

        Bus::assertDispatched(UpdateOrganizationStatsJob::class, function ($job) {
            return $this->jobProperty($job, 'organization_id') === null;
        });
        Bus::assertDispatched(UpdateOrganizationStatsJob::class, function ($job) use ($organization) {
            return $this->jobProperty($job, 'organization_id') == $organization->id;
        });
    }

    public function testUpdateOrganizationStatsRejectsUnknownOrganization()
    {
        $this->artisan('fif:update-organization-stats', ['--organization' => '999999999999'])
            ->expectsOutput('Unknown organization id 999999999999. Exit.')
            ->assertExitCode(0)
            ->run();

        Bus::assertNotDispatched(UpdateOrganizationStatsJob::class);
    }

    /**
     * Fills cpvs.trimmed_code: the code without trailing zeros, but with at least 2 characters.
     */
    public function testRampTrimmedCpvCodes()
    {
        // no real CPV codes start with 99 or 20
        DB::table('cpvs')->insert([
            ['code' => '99110000', 'trimmed_code' => null, 'control_digit' => 0, 'name' => 'Testbranche'],
            ['code' => '20000000', 'trimmed_code' => null, 'control_digit' => 0, 'name' => 'Testbranche'],
        ]);

        $this->artisan('fif:ramp-trimmed-cpv-codes')->assertExitCode(0)->run();

        $this->assertDatabaseHas('cpvs', ['code' => '99110000', 'trimmed_code' => '9911']);
        $this->assertDatabaseHas('cpvs', ['code' => '20000000', 'trimmed_code' => '20']);
    }

    /**
     * Reads a protected property of a dispatched job.
     */
    private function jobProperty($job, $property)
    {
        return (function () use ($property) {
            return $this->{$property};
        })->call($job);
    }

    private function insertKerndaten(array $attributes)
    {
        return DB::connection('mysql_scraper')->table('kerndaten')->insertGetId(array_merge([
            'quelle'       => 'test',
            'item_id'      => 'command-test-'.Str::random(8),
            'item_lastmod' => '2026-01-01 12:00:00',
            'version'      => 1,
            'xml'          => '<KD_8_2_Z1/>',
        ], $attributes));
    }
}

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
use Tests\Fixtures\Eforms;

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

    /**
     * Re-creates the further information from the xml: no new version, other columns stay as they are.
     */
    public function testUpdateFromXmlRecreatesFurtherInformation()
    {
        $this->withScraperTransaction(function () {
            $kerndatenId = $this->insertKerndaten(['xml' => Eforms::xml('can-standard')]);
            $eforms      = $this->createDataset([
                'source_format'        => 'eforms',
                'scraper_kerndaten_id' => $kerndatenId,
                'further_information'  => json_encode(['sdk' => '1.13', 'groups' => []]),
                'title'                => 'Titel bleibt',
            ]);
            $kerndaten   = $this->createDataset(['scraper_kerndaten_id' => $kerndatenId]);

            $this->artisan('fif:update-from-xml', ['--further-information' => true, '--format' => 'all', '--dataset_id' => [$eforms, $kerndaten]])
                ->assertExitCode(0)->run();

            $info = json_decode(DB::table('datasets')->where('id', $eforms)->value('further_information'), true);
            $this->assertContains('Angebot: TEN-0001', array_column($info['groups'], 'title'));
            $this->assertSame('Titel bleibt', DB::table('datasets')->where('id', $eforms)->value('title'));
            $this->assertSame(1, DB::table('datasets')->where('scraper_kerndaten_id', $kerndatenId)->where('source_format', 'eforms')->count());
            // Kerndaten datasets have no further information
            $this->assertNull(DB::table('datasets')->where('id', $kerndaten)->value('further_information'));
        });
    }

    /**
     * Offerors are re-derived from the xml and linked to the organization of their national id, in place.
     */
    public function testUpdateFromXmlRelinksOfferors()
    {
        $this->withScraperTransaction(function () {
            [$datasetId, $main, $extra, $identified] = $this->datasetWithUnidentifiedOfferors();

            $this->artisan('fif:update-from-xml', ['--offerors' => true, '--dataset_id' => [$datasetId]])->assertExitCode(0)->run();

            $this->assertDatabaseHas('offerors', ['id' => $main, 'national_id' => '99999', 'organization_id' => $identified->id, 'is_extra' => 0]);
            $this->assertDatabaseHas('offerors', ['id' => $extra, 'national_id' => '999998z', 'is_extra' => 1]);
            // in place: same dataset, no new one
            $this->assertSame(1, DB::table('datasets')->where('id', $datasetId)->count());
        });
    }

    public function testUpdateFromXmlDryRunChangesNothing()
    {
        $this->withScraperTransaction(function () {
            [$datasetId, $main, $extra] = $this->datasetWithUnidentifiedOfferors();
            $organizations = DB::table('organizations')->count();

            $this->artisan('fif:update-from-xml', ['--offerors' => true, '--dry-run' => true, '--dataset_id' => [$datasetId]])
                ->expectsOutputToContain('national_id NULL')
                ->assertExitCode(0)->run();

            $this->assertDatabaseHas('offerors', ['id' => $main, 'national_id' => null]);
            $this->assertDatabaseHas('offerors', ['id' => $extra, 'national_id' => null]);
            // no organization is created for the additional offeror, whose id has no organization yet
            $this->assertSame($organizations, DB::table('organizations')->count());
        });
    }

    /**
     * If the number of contracting bodies differs, offerors can't be matched: the dataset is left alone.
     */
    public function testUpdateFromXmlSkipsDatasetsWithOtherNumberOfOfferors()
    {
        $this->withScraperTransaction(function () {
            [$datasetId, $main, $extra] = $this->datasetWithUnidentifiedOfferors();
            DB::table('offerors')->where('id', $extra)->delete();

            $this->artisan('fif:update-from-xml', ['--offerors' => true, '--dataset_id' => [$datasetId]])
                ->expectsOutputToContain('skipped')
                ->assertExitCode(0)->run();

            $this->assertDatabaseHas('offerors', ['id' => $main, 'national_id' => null]);
        });
    }

    public function testUpdateFromXmlNeedsSomethingToDo()
    {
        $this->artisan('fif:update-from-xml')->assertExitCode(1)->run();
    }

    /**
     * An eForms dataset whose two offerors (main and additional, see the fixture) were stored without national id
     * and linked to an unidentified organization.
     *
     * @return array  [ dataset id, main offeror id, additional offeror id, organization with the main national id ]
     */
    private function datasetWithUnidentifiedOfferors()
    {
        $identified   = $this->createOrganization(['gkz' => '99999', 'is_identified' => 1]);
        $unidentified = $this->createOrganization();
        $datasetId    = $this->createDataset([
            'source_format'        => 'eforms',
            'scraper_kerndaten_id' => $this->insertKerndaten(['xml' => Eforms::xml('can-standard')]),
        ], $unidentified);
        DB::table('offerors')->where('dataset_id', $datasetId)->update(['national_id' => null]);
        $this->addOfferor($datasetId, $unidentified, true);

        $offerors = DB::table('offerors')->where('dataset_id', $datasetId)->orderBy('is_extra')->pluck('id')->all();

        return [$datasetId, $offerors[0], $offerors[1], $identified];
    }

    /**
     * Commands read the scraper database (connection mysql_scraper), which is not covered by DatabaseTransactions.
     */
    private function withScraperTransaction(callable $test)
    {
        $scraper = DB::connection('mysql_scraper');
        $scraper->beginTransaction();

        try {
            $test();
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
        $this->createScraperQuelle();

        return DB::connection('mysql_scraper')->table('kerndaten')->insertGetId(array_merge([
            'quelle'       => 'test',
            'item_id'      => 'command-test-'.Str::random(8),
            'item_lastmod' => '2026-01-01 12:00:00',
            'version'      => 1,
            'xml'          => '<KD_8_2_Z1/>',
        ], $attributes));
    }
}

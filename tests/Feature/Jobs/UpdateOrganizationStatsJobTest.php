<?php

namespace Tests\Feature\Jobs;

use App\Jobs\UpdateOrganizationStatsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The job is tested for single organizations only: without an id it updates every organization in the database.
 */
class UpdateOrganizationStatsJobTest extends JobTestCase
{
    // dataset type with dataset_types.end = 0 ("Ausschreibung")
    const TYPE_AUSSCHREIBUNG = 'KD_8_2_Z1';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('dataset_types')->insertOrIgnore([
            'code'        => self::TYPE_AUSSCHREIBUNG,
            'name'        => 'Test',
            'description' => 'Testausschreibung',
            'end'         => 0,
        ]);
    }

    public function testCountsCurrentEnabledDatasetsOfOrganization()
    {
        $organization = $this->createOrganization();

        // as offeror: one Ausschreibung, two Aufträge
        $this->createDataset(['type_code' => self::TYPE_AUSSCHREIBUNG], $organization);
        $this->createDataset(['val_total' => 1000], $organization);
        $this->createDataset(['val_total' => 2000], $organization);
        // not counted: an older version and a disabled dataset
        $this->createDataset(['val_total' => 4000, 'is_current_version' => 0], $organization);
        $this->createDataset(['val_total' => 8000, 'disabled_at' => now()], $organization);
        // as contractor: one Auftrag
        $this->addContractor($this->createDataset(['val_total' => 500]), $organization);

        (new UpdateOrganizationStatsJob($organization->id))->handle();

        $this->assertDatabaseHas('organizations', [
            'id'                             => $organization->id,
            'count_ausschreibung_offeror'    => 1,
            'count_auftrag_offeror'          => 2,
            'count_offeror'                  => 3,
            'val_total_auftrag_offeror'      => 3000,
            'count_ausschreibung_contractor' => 0,
            'count_auftrag_contractor'       => 1,
            'count_contractor'               => 1,
            'val_total_auftrag_contractor'   => 500,
        ]);
    }

    /**
     * Aufträge without value (val_total null) are counted, the volume becomes 0.
     */
    public function testAuftraegeWithoutValueCountWithVolumeZero()
    {
        $organization = $this->createOrganization(['val_total_auftrag_offeror' => 1000]);
        $this->createDataset(['val_total' => null], $organization);

        (new UpdateOrganizationStatsJob($organization->id))->handle();

        $this->assertDatabaseHas('organizations', [
            'id'                        => $organization->id,
            'count_auftrag_offeror'     => 1,
            'val_total_auftrag_offeror' => 0,
        ]);
    }

    public function testUnknownOrganizationIsLoggedAndSkipped()
    {
        Log::shouldReceive('debug');
        Log::shouldReceive('error')->once()->with('Unknown organization id 999999999999. Exit.');

        (new UpdateOrganizationStatsJob(999999999999))->handle();
    }

    /**
     * TODO: bug (DOCUMENTATION.md, known quirk 4) — UpdateOrganizationStatsJob only writes the counts of an
     * organization that still has matching datasets, so they never go back to 0, e.g. after its last dataset was
     * disabled. The organization then stays in /auftraggeber and on the frontpage. Reset the values for
     * organizations without results, then remove markTestIncomplete() here.
     */
    public function testCountsAreResetWhenOrganizationHasNoDatasetsLeft()
    {
        $this->markTestIncomplete('UpdateOrganizationStatsJob never resets counts to 0.');

        $organization = $this->createOrganization([
            'count_auftrag_offeror'     => 5,
            'count_offeror'             => 5,
            'val_total_auftrag_offeror' => 1000,
        ]);
        $this->createDataset(['disabled_at' => now()], $organization);

        (new UpdateOrganizationStatsJob($organization->id))->handle();

        $this->assertDatabaseHas('organizations', [
            'id'                        => $organization->id,
            'count_auftrag_offeror'     => 0,
            'count_offeror'             => 0,
            'val_total_auftrag_offeror' => 0,
        ]);
    }
}

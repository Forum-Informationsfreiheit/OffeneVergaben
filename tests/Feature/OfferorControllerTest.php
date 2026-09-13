<?php

namespace Tests\Feature;

use Illuminate\Support\Str;

class OfferorControllerTest extends PublicTestCase
{
    // more than any real volume (in cents), so these organizations are first when sorted by volume
    const TOP_VOLUME = 900000000000000000;

    /**
     * The list uses the precomputed organization stats: only organizations with count_offeror > 0.
     */
    public function testIndexListsOnlyOrganizationsThatAreOfferors()
    {
        $offeror    = $this->createOrganization(['count_offeror' => 1, 'val_total_auftrag_offeror' => self::TOP_VOLUME]);
        $contractor = $this->createOrganization(['count_contractor' => 1, 'val_total_auftrag_offeror' => self::TOP_VOLUME]);

        $response = $this->get(route('public::auftraggeber', ['sort' => '-sum']));

        $response->assertStatus(200);
        $response->assertViewIs('public.offerors.index');
        $response->assertSee($offeror->name);
        $response->assertDontSee($contractor->name);
    }

    public function testShowDisplaysDatasetsAndStats()
    {
        $organization = $this->createOrganization();
        $contractor   = $this->createOrganization();
        $cpv          = $this->createCpv('99100000');
        $title        = 'Testauftrag '.Str::random(10);
        $id           = $this->createDataset([
            'title'               => $title,
            'cpv_code'            => $cpv->code,
            'val_total'           => 1234500,
            'nb_tenders_received' => 3,
        ], $organization);
        $this->addContractor($id, $contractor);

        $response = $this->get(route('public::show-auftraggeber', $organization->id));

        $response->assertStatus(200);
        $response->assertViewIs('public.offerors.show');
        $response->assertSee($organization->name);
        $response->assertSee($title);
        $response->assertSee($contractor->name);

        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats->totalCount);
        $this->assertSame(1234500, $stats->totalVal);
        $this->assertSame(3, $stats->totalTenders);
        $this->assertEquals([$cpv->code], $stats->topCpvs->pluck('cpv_code')->all());
        $this->assertEquals([$contractor->id], $stats->topContractors->pluck('organization_id')->all());
    }

    public function testShowListsDatasetsWithOrganizationAsAdditionalOfferor()
    {
        $organization = $this->createOrganization();
        $title        = 'Testauftrag '.Str::random(10);
        $id           = $this->createDataset(['title' => $title]);
        $this->addOfferor($id, $organization, true);

        $response = $this->get(route('public::show-auftraggeber', $organization->id));

        $response->assertStatus(200);
        $response->assertSee($title);
    }

    public function testShowUnknownOrganizationReturnsNotFound()
    {
        $this->get(route('public::show-auftraggeber', 0))->assertNotFound();
    }

    /**
     * TODO: bug — DatasetFilter::search() uses offerors.name and contractors.name, but OfferorController@show
     * joins contractors only when sorting by contractor, so ?search= fails with an SQL error (HTTP 500).
     * Also join contractors when search is used (like DatasetController@index), then remove
     * markTestIncomplete() here.
     */
    public function testShowSearchFiltersDatasets()
    {
        $this->markTestIncomplete('OfferorController@show does not join contractors for ?search= (SQL error, HTTP 500).');

        $organization = $this->createOrganization();
        $token        = Str::random(10);
        $other        = 'Anderer Auftrag '.Str::random(10);
        $this->createDataset(['title' => 'Gefunden '.$token], $organization);
        $this->createDataset(['title' => $other], $organization);

        $response = $this->get(route('public::show-auftraggeber', ['id' => $organization->id, 'search' => $token]));

        $response->assertStatus(200);
        $response->assertSee('Gefunden '.$token);
        $response->assertDontSee($other);
    }

    /**
     * TODO: bug — in OfferorController::getOfferorStats() the topCpvs and topContractors queries don't exclude
     * disabled datasets (the other stats do), so disabled datasets still show up in the stats block. Add
     * ->where('datasets.disabled_at', null) to both, then remove markTestIncomplete() here.
     */
    public function testStatsIgnoreDisabledDatasets()
    {
        $this->markTestIncomplete('topCpvs and topContractors in OfferorController::getOfferorStats() include disabled datasets.');

        $organization = $this->createOrganization();
        $id           = $this->createDataset([
            'cpv_code'    => $this->createCpv('99100000')->code,
            'disabled_at' => now(),
        ], $organization);
        $this->addContractor($id, $this->createOrganization());

        $response = $this->get(route('public::show-auftraggeber', $organization->id));

        $stats = $response->viewData('stats');
        $this->assertCount(0, $stats->topCpvs);
        $this->assertCount(0, $stats->topContractors);
    }
}

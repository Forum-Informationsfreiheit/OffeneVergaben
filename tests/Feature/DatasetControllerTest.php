<?php

namespace Tests\Feature;

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatasetControllerTest extends PublicTestCase
{
    public function testIndexListsDatasetsWithTheirOfferor()
    {
        $offeror = $this->createOrganization();
        $title   = 'Testauftrag '.Str::random(10);
        $this->createDataset(['title' => $title], $offeror);

        $response = $this->get(route('public::auftraege'));

        $response->assertStatus(200);
        $response->assertViewIs('public.datasets.index');
        $response->assertSee($title);
        $response->assertSee($offeror->name);
    }

    public function testIndexHidesOlderVersionsAndDisabledDatasets()
    {
        $suffix    = Str::random(10);
        $metasetId = $this->createMetaset();
        $this->createDataset([
            'metaset_id'         => $metasetId,
            'version'            => 1,
            'is_current_version' => 0,
            'title'              => 'Alte Version '.$suffix,
        ]);
        $this->createDataset(['metaset_id' => $metasetId, 'version' => 2, 'title' => 'Aktuelle Version '.$suffix]);
        $this->createDataset(['title' => 'Deaktiviert '.$suffix, 'disabled_at' => now()]);

        $response = $this->get(route('public::auftraege'));

        $response->assertStatus(200);
        $response->assertSee('Aktuelle Version '.$suffix);
        $response->assertDontSee('Alte Version '.$suffix);
        $response->assertDontSee('Deaktiviert '.$suffix);
    }

    public function testIndexFiltersByCpvPrefixAndShowsCpvName()
    {
        $suffix = Str::random(10);
        $cpv    = $this->createCpv('99100000');
        $this->createDataset(['title' => 'Mit CPV '.$suffix, 'cpv_code' => $cpv->code]);
        $this->createDataset(['title' => 'Ohne CPV '.$suffix]);

        $response = $this->get(route('public::auftraege', ['cpv' => '991*']));

        $response->assertStatus(200);
        $response->assertSee('Mit CPV '.$suffix);
        $response->assertDontSee('Ohne CPV '.$suffix);
        $response->assertViewHas('cpvName', $cpv->name);
        // the canonical query string for the subscribe form, see SubscriptionController@validateSubscriptionQuery
        $response->assertViewHas('queryString', 'cpv=991%2A');
    }

    /**
     * search also matches the offeror name, which needs the offerors table joined.
     */
    public function testIndexSearchMatchesOfferorName()
    {
        $token   = Str::random(10);
        $suffix  = Str::random(10);
        $offeror = $this->createOrganization(['name' => 'Gemeinde '.$token]);
        $this->createDataset(['title' => 'Gefunden '.$suffix], $offeror);
        $this->createDataset(['title' => 'Nicht gesucht '.$suffix]);

        $response = $this->get(route('public::auftraege', ['search' => $token]));

        $response->assertStatus(200);
        $response->assertSee('Gefunden '.$suffix);
        $response->assertDontSee('Nicht gesucht '.$suffix);
    }

    public function testShowDisplaysDataset()
    {
        $offeror    = $this->createOrganization();
        $contractor = $this->createOrganization();
        $title      = 'Testauftrag '.Str::random(10);
        $id         = $this->createDataset(['title' => $title, 'description' => 'Sanierung der Ortsdurchfahrt'], $offeror);
        $this->addContractor($id, $contractor);

        $response = $this->get(route('public::auftrag', $id));

        $response->assertStatus(200);
        $response->assertViewIs('public.datasets.show');
        $response->assertSee($title);
        $response->assertSee('Sanierung der Ortsdurchfahrt');
        $response->assertSee($offeror->name);
        $response->assertSee($contractor->name);
        $response->assertSee(route('public::auftragsxml', ['id' => $id]));
    }

    public function testShowLinksOtherVersions()
    {
        $metasetId = $this->createMetaset();
        $version1  = $this->createDataset(['metaset_id' => $metasetId, 'version' => 1, 'is_current_version' => 0]);
        // with description, see testShowWithoutDescriptionClosesOutputBuffers()
        $version2  = $this->createDataset(['metaset_id' => $metasetId, 'version' => 2, 'description' => 'Zweite Version']);

        $response = $this->get(route('public::auftrag', $version2));

        $response->assertStatus(200);
        $response->assertSee('<a href="'.route('public::auftrag', $version1).'">1</a>');
    }

    /**
     * TODO: bug — datasets/show.blade.php passes the title and the description to inline @section(). For null (a
     * dataset without description or title) Blade starts a block section instead, whose output buffer is never
     * closed. PHPUnit reports such tests as risky and ignores their coverage, so the other tests of the show page
     * create datasets with description. Only define the sections for non-empty values, then remove
     * markTestIncomplete() here.
     */
    public function testShowWithoutDescriptionClosesOutputBuffers()
    {
        $this->markTestIncomplete('datasets/show.blade.php leaves an output buffer open for datasets without description.');

        $id    = $this->createDataset(['description' => null]);
        $level = ob_get_level();

        $response = $this->get(route('public::auftrag', $id));

        $response->assertStatus(200);
        $this->assertSame($level, ob_get_level());
    }

    public function testShowUnknownDatasetReturnsNotFound()
    {
        $this->get(route('public::auftrag', 0))->assertNotFound();
    }

    /**
     * @dataProvider visitorProvider
     */
    public function testDisabledDatasetIsHiddenFromVisitors($roleId)
    {
        $id = $this->createDataset(['disabled_at' => now()]);
        $this->actingAsRole($roleId);

        $this->get(route('public::auftrag', $id))->assertNotFound();
        $this->get(route('public::auftragsxml', $id))->assertNotFound();
    }

    public function testEditorsCanSeeDisabledDataset()
    {
        // with description, see testShowWithoutDescriptionClosesOutputBuffers()
        $id = $this->createDataset(['disabled_at' => now(), 'description' => 'Deaktivierter Auftrag']);

        $response = $this->actingAsRole(Role::EDITOR)->get(route('public::auftrag', $id));

        $response->assertStatus(200);
        $response->assertSee('<strong>deaktiviert</strong>');
    }

    /**
     * The XML is read from kerndaten in the scraper database (connection mysql_scraper), which is not
     * covered by DatabaseTransactions, so this test rolls back its own transaction there.
     */
    public function testXmlReturnsSourceXml()
    {
        $xml     = '<KD_8_2_Z1><TITLE>Testauftrag</TITLE></KD_8_2_Z1>';
        $scraper = DB::connection('mysql_scraper');
        $scraper->beginTransaction();

        try {
            $kerndatenId = $scraper->table('kerndaten')->insertGetId([
                'quelle'       => 'test',
                'item_id'      => 'public-test-'.Str::random(8),
                'item_lastmod' => '2026-01-01 12:00:00',
                'version'      => 1,
                'xml'          => $xml,
            ]);
            $id = $this->createDataset(['scraper_kerndaten_id' => $kerndatenId]);

            $response = $this->get(route('public::auftragsxml', $id));

            $response->assertStatus(200);
            $this->assertStringStartsWith('text/xml', $response->headers->get('Content-Type'));
            $this->assertSame($xml, $response->getContent());
        } finally {
            $scraper->rollBack();
        }
    }
}

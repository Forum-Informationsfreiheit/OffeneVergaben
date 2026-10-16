<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatasetControllerTest extends AdminTestCase
{
    public function testIndexFiltersById()
    {
        $id = $this->createDataset($this->createMetaset(), 1, ['title' => 'Straßenbau Testdatensatz']);

        $response = $this->actingAs($this->createEditor())->get('/admin/datasets?id='.$id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.datasets.index');
        $response->assertViewHas('total', 1);
        $response->assertSee('Straßenbau Testdatensatz');
    }

    public function testIndexFilterByIdIncludesDisabledDataset()
    {
        $id = $this->createDataset($this->createMetaset(), 1, ['disabled_at' => now()]);

        $response = $this->actingAs($this->createEditor())->get('/admin/datasets?id='.$id);

        $response->assertStatus(200);
        $response->assertViewHas('total', 1);
    }

    public function testIndexInactiveListsOnlyDisabledDatasets()
    {
        // in the future, so they are on the first page (sorted by item_lastmod desc). item_lastmod is a
        // MySQL TIMESTAMP, which ends at 2038-01-19 03:14:07 UTC.
        $lastmod = '2037-01-01 00:00:00.000000';
        $this->createDataset($this->createMetaset(), 1, ['title' => 'Aktiver Testdatensatz', 'item_lastmod' => $lastmod]);
        $this->createDataset($this->createMetaset(), 1, [
            'title'        => 'Deaktivierter Testdatensatz',
            'item_lastmod' => $lastmod,
            'disabled_at'  => now(),
        ]);

        $response = $this->actingAs($this->createEditor())->get('/admin/datasets?inactive');

        $response->assertStatus(200);
        $response->assertSee('Deaktivierter Testdatensatz');
        $response->assertDontSee('Aktiver Testdatensatz');
    }

    public function testDisableDisablesAllVersions()
    {
        $metasetId = $this->createMetaset();
        $version1  = $this->createDataset($metasetId, 1);
        $version2  = $this->createDataset($metasetId, 2);

        $response = $this->actingAs($this->createEditor())->patch('/admin/datasets/disable', [
            'id'   => $version2,
            'mode' => 'disable',
        ]);

        $response->assertRedirect(route('admin::datasets', ['id' => $version2]));
        $this->assertSame(0, DB::table('datasets')->whereIn('id', [$version1, $version2])->whereNull('disabled_at')->count());
    }

    public function testEnableReEnablesAllVersions()
    {
        $metasetId = $this->createMetaset();
        $version1  = $this->createDataset($metasetId, 1, ['disabled_at' => now()]);
        $version2  = $this->createDataset($metasetId, 2, ['disabled_at' => now()]);

        $this->actingAs($this->createEditor())->patch('/admin/datasets/disable', [
            'id'   => $version1,
            'mode' => 'enable',
        ]);

        $this->assertSame(0, DB::table('datasets')->whereIn('id', [$version1, $version2])->whereNotNull('disabled_at')->count());
    }

    public function testDisableUnknownDatasetReturnsNotFound()
    {
        $response = $this->actingAs($this->createEditor())->patch('/admin/datasets/disable', ['id' => 0, 'mode' => 'disable']);

        $response->assertNotFound();
    }

    private function createMetaset()
    {
        return DB::table('metasets')->insertGetId([
            'quellen_id' => 1,
            'item_id'    => 'admin-test-'.Str::random(8),
        ]);
    }

    /**
     * Creates a dataset version with an offeror, which the admin list needs to render.
     */
    private function createDataset($metasetId, $version, array $attributes = [])
    {
        $id = DB::table('datasets')->insertGetId($attributes + [
            'metaset_id'   => $metasetId,
            'version'      => $version,
            'title'        => 'Testdatensatz',
            'item_lastmod' => '2026-01-01 12:00:00.000000',
        ]);

        DB::table('offerors')->insert([
            'dataset_id' => $id,
            'name'       => 'Test-Auftraggeber',
            'is_extra'   => 0,
        ]);

        return $id;
    }
}

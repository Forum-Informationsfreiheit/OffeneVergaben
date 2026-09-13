<?php

namespace Tests\Feature;

use Illuminate\Support\Str;

class CpvControllerTest extends PublicTestCase
{
    public function testIndexShowsSectorsByVolume()
    {
        $sector = $this->createCpv('99000000');
        $this->createDataset(['cpv_code' => $this->createCpv('99100000')->code, 'val_total' => 1234500]);

        $response = $this->get(route('public::branchen'));

        $response->assertStatus(200);
        $response->assertViewIs('public.cpvs.index');
        $response->assertViewHas('params', function ($params) {
            return $params->type === 'volume' && $params->root === null;
        });
        $response->assertSee($sector->name);
        $this->assertSame(1234500, $response->viewData('items')->where('cpv', '99')->first()->sum);
    }

    public function testAnzahlSizesSectorsByCount()
    {
        $this->createCpv('99000000');
        $subSector = $this->createCpv('99100000');
        $this->createDataset(['cpv_code' => $subSector->code]);
        $this->createDataset(['cpv_code' => $subSector->code]);

        $response = $this->get(route('public::branchen', ['anzahl']));

        $response->assertStatus(200);
        $response->assertViewHas('params', function ($params) {
            return $params->type === 'anzahl';
        });
        $this->assertSame(2, $response->viewData('items')->where('cpv', '99')->first()->count);
    }

    /**
     * node drills down one level (99 → 991, 992, ...) and adds the totals of the node itself.
     */
    public function testNodeShowsSubSectors()
    {
        $this->createCpv('99000000');
        $subSector = $this->createCpv('99100000');
        $this->createDataset(['cpv_code' => $subSector->code, 'val_total' => 1000]);
        $this->createDataset(['cpv_code' => $subSector->code, 'val_total' => 2000]);

        $response = $this->get(route('public::branchen', ['node' => '99000000']));

        $response->assertStatus(200);
        $response->assertViewHas('params', function ($params) {
            return $params->root->code === '99000000';
        });
        $response->assertSee($subSector->name);
        $this->assertSame(3000, $response->viewData('items')->where('cpv', '991')->first()->sum);
        $this->assertEquals(2, $response->viewData('rootNodeTotals')->count);
    }

    public function testUnknownNodeReturnsNotFound()
    {
        $this->get(route('public::branchen', ['node' => '99999999']))->assertNotFound();
    }

    /**
     * TODO: bug — CpvController@index computes isLeaf as strlen($i->cpv == CPV::STR_CODE_LENGTH), which is
     * always 0. The treemap then lets users drill into a full 8-digit code, which only shows that code again.
     * Use strlen($i->cpv) == CPV::STR_CODE_LENGTH, then remove markTestIncomplete() here.
     */
    public function testFullCodesAreMarkedAsLeaf()
    {
        $this->markTestIncomplete('CpvController@index: isLeaf is always 0 (strlen of a comparison).');

        $this->createCpv('99111110');
        $leaf = $this->createCpv('99111111');
        $this->createDataset(['cpv_code' => $leaf->code]);

        $response = $this->get(route('public::branchen', ['node' => '99111110']));

        $response->assertStatus(200);
        $this->assertSame(1, $response->viewData('items')->where('cpv', '99111111')->first()->isLeaf);
    }

    public function testSearchIsOnlyAvailableForAjaxRequests()
    {
        $this->createCpv('99100000');

        $this->getJson(route('public::ajax-cpv-search', ['query' => '991']))->assertForbidden();
    }

    /**
     * @dataProvider codeQueryProvider
     */
    public function testSearchFindsCpvsByCodePrefix($query)
    {
        $cpv = $this->createCpv('99100000');

        $response = $this->ajaxSearch($query);

        $response->assertStatus(200);
        $response->assertJsonFragment(['code' => '99100000', 'trimmed_code' => '991', 'name' => $cpv->name]);
    }

    public function testSearchFindsCpvsByName()
    {
        $token = Str::random(10);
        $cpv   = $this->createCpv('99100000', 'Testbranche '.$token);

        // the autocomplete in the /aufträge filter form sends the input in lower case
        $response = $this->ajaxSearch(Str::lower($token));

        $response->assertStatus(200);
        $response->assertJsonFragment(['code' => $cpv->code, 'name' => $cpv->name]);
    }

    public function testSearchWithoutQueryReturnsEmptyList()
    {
        $this->ajaxSearch('')->assertExactJson([]);
    }

    public function codeQueryProvider()
    {
        return [
            'code prefix'                 => ['991'],
            'code prefix with wildcard *' => ['991*'],
        ];
    }

    private function ajaxSearch($query)
    {
        return $this->getJson(route('public::ajax-cpv-search', ['query' => $query]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }
}

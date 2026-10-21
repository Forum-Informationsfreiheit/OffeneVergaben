<?php

namespace Tests\Feature\Http\Filters;

use App\Http\Filters\OrganizationAsContractorFilter;
use App\Http\Filters\OrganizationAsOfferorFilter;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\DatabaseTestCase;

/**
 * The sort parameter of /auftraggeber and /lieferanten, applied to the same query as in OfferorController@index and
 * ContractorController@index, but only for the organizations created by the test.
 */
class OrganizationFiltersTest extends DatabaseTestCase
{
    #[DataProvider('filterProvider')]
    public function testSortsByNameCountAndVolume($filterClass, $countColumn, $volumeColumn)
    {
        $organizations = [
            'a' => $this->createOrganization(['name' => 'Aaa', $countColumn => 3, $volumeColumn => 200])->id,
            'b' => $this->createOrganization(['name' => 'Bbb', $countColumn => 1, $volumeColumn => 300])->id,
            'c' => $this->createOrganization(['name' => 'Ccc', $countColumn => 2, $volumeColumn => 100])->id,
        ];
        $sorted = function ($sort) use ($filterClass, $countColumn, $volumeColumn, $organizations) {
            $ids = Organization::query()
                ->filter(new $filterClass(Request::create('/?sort='.$sort)))
                ->select([
                    'organizations.*',
                    DB::raw($countColumn.' as datasets_count'),
                    DB::raw($volumeColumn.' as sum_val_total'),
                ])
                ->whereIn('id', $organizations)
                ->pluck('id')
                ->all();

            return array_map(function ($id) use ($organizations) {
                return array_search($id, $organizations);
            }, $ids);
        };

        $this->assertSame(['a', 'b', 'c'], $sorted('name'));
        $this->assertSame(['c', 'b', 'a'], $sorted('-name'));
        $this->assertSame(['b', 'c', 'a'], $sorted('count'));
        $this->assertSame(['b', 'a', 'c'], $sorted('-sum'));
    }

    #[DataProvider('filterProvider')]
    public function testIsSortedBy($filterClass)
    {
        $this->assertSame('asc', $this->applyFilter($filterClass, 'sort=name')->isSortedBy('name'));
        $this->assertSame('desc', $this->applyFilter($filterClass, 'sort=-count')->isSortedBy('count'));
        $this->assertFalse($this->applyFilter($filterClass, 'sort=-count')->isSortedBy('name'));
        // unknown fields are not sorted by
        $this->assertFalse($this->applyFilter($filterClass, 'sort=national_id')->isSortedBy('national_id'));
    }

    #[DataProvider('filterProvider')]
    public function testHasAndMakeSortUrl($filterClass)
    {
        $filter = $this->applyFilter($filterClass, 'page=2&sort=name');

        $this->assertTrue($filter->has('sort'));
        $this->assertFalse($filter->has('search'));
        $this->assertSame(url()->current().'?page=2&sort=-count', $filter->makeSortUrl('count', 'desc'));
    }

    public static function filterProvider()
    {
        return [
            'Auftraggeber' => [OrganizationAsOfferorFilter::class, 'count_offeror', 'val_total_auftrag_offeror'],
            'Lieferanten'  => [OrganizationAsContractorFilter::class, 'count_contractor', 'val_total_auftrag_contractor'],
        ];
    }

    private function applyFilter($filterClass, $queryString)
    {
        $filter = new $filterClass(Request::create('/?'.$queryString));
        Organization::query()->filter($filter);

        return $filter;
    }
}

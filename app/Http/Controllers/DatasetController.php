<?php

namespace App\Http\Controllers;

use App\CPV;
use App\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DatasetController extends Controller
{
    /**
     * IMPORTANT because not self explanatory.
     * Laravels Eloquent is a fine ORM but when we are dealing with more than one table
     * and have to query multiple relationships Eloquent gets more in the way than it is helping.
     *
     * Therefor don't use Eloquent when building the base query for the index view !!!
     *
     * How it works:
     * 1. Build the base query by using Laravel QueryBuilder (NOT Eloquent)
     *    The base query joins two external tables: offerors and contractors
     * 2. The DatasetFilter further operates on this query and applies
     *    filter- and sort-logic.
     * 3. Do the pagination
     * 4. The result of this query are just the Dataset-IDs.
     *    These are correctly filtered and ordered and
     *    represent only one "page"
     *    (one page is 20 items at the time of writing this).
     * 5. With the correct ids at hand now we use eloquent to actually
     *    load up full eloquent models for those 20 items we are going to display
     *    (they are just very convenient to handle in views)
     *    Note the code that says DB::raw("FIELD......") this is to preserve
     *    the original sort order (see 4.)
     *
     *
     * @param DatasetFilter $filters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(DatasetFilter $filters) {
        $query = Dataset::indexQuery()->filter($filters);

        if (!$filters->has('sort')) {
            // apply default sorting, item aktualisierungsdatum
            $query->orderBy('item_lastmod','desc');
        }

        $totalItems = $query->count();
        $data       = $query->paginate(20);

        if (count($data) > 0) {
            // now load the appropriate models for the view
            $items = Dataset::loadInOrder($data->pluck('id')->toArray())
                ->withCount('contractors')
                ->withCount('offerors')
                ->get();
        } else {
            $items = [];
        }

        // Current query string will be stored within a hidden input field
        // in case the user wants to subscribe
        $appliedFilters = $filters->getAppliedFilters();
        ksort($appliedFilters);
        $queryString = http_build_query($appliedFilters);

        return view('public.datasets.index',compact('items','totalItems','filters','data','queryString'));
    }

    public function indexWithEloquentQueryFilter(DatasetFilter $filters) {

        // this looks super clean, but just fails to work
        // with filterable & sortable columns from relationship tables
        // so .... its here for future reference but will not be used

        $query = Dataset::with('offeror')->with('contractor')->filter($filters);

        $totalItems   = $query->count();
        $data         = $query->paginate(20);

        $items = $data;

        return view('public.datasets.index',compact('items','totalItems','filters'));
    }

    public function indexOld(Request $request) {
        $order = $request->has('orderBy') ? $request->input('orderBy') : 'offerors.name';
        $direction = $request->has('desc') ? 'desc' : 'asc';
        $cpvFilter = $request->has('cpvFilter') ? $request->input('cpvFilter') : null;
        $offerorFilter = $request->has('offerorFilter') ? $request->input('offerorFilter') : null;
        $contractorFilter = $request->has('contractorFilter') ? $request->input('contractorFilter') : null;

        $cpv = null;

        // todo need to use same query for list data as for count value
        // this includes filters !!!
        $totalItems = Dataset::current()->count();

        $query = Dataset::select([
            'datasets.*',
            'offerors.name as offeror_name',
            'offerors.national_id as offeror_national_id',
            'res.created_at as scraped_at'
        ]);
        $query->join('offerors', 'datasets.id', '=', 'offerors.dataset_id');
        //$query->where('offerors.is_extra',0);
        $query->join('scraper_results as res','datasets.result_id','=','res.id');

        if ($cpvFilter) {
            // quick check cpv
            $cpv = CPV::findOrFail($cpvFilter);

            $query->join('cpv_dataset as cd','datasets.id','=','cd.dataset_id');
            $query->where('cd.cpv_code','=',$cpvFilter);
        }

        if ($offerorFilter) {
            // quick check org
            $org = Organization::findOrFail($offerorFilter);

            $query->where('offerors.organization_id',$org->id);
        }

        if ($contractorFilter) {
            // quick check org
            $org = Organization::findOrFail($contractorFilter);

            $query->join('contractors','datasets.id','=','contractors.dataset_id');
            $query->where('contractors.organization_id',$org->id);
        }

        $query->orderBy($order,$direction);

        $showAll = $request->has('showAll');
        if ($showAll) {
            $items = $query->get();
        } else {
            $items = $query->paginate(20);
        }

        $paramsString = "orderBy=$order" . ($direction == "desc" ? "&desc=1" : "");

        return view('public.datasets.index',compact('items','totalItems','showAll','paramsString','cpv','cpvFilter'));
    }

    public function show($id) {
        if (Gate::allows('view-disabled-datasets')) {
            $dataset = Dataset::withoutGlobalScope('not_disabled')->findOrFail($id);
        } else {
            $dataset = Dataset::findOrFail($id);
        }

        return view('public.datasets.show',compact('dataset'));
    }

    public function showXml($id) {
        if (Gate::allows('view-disabled-datasets')) {
            $dataset = Dataset::withoutGlobalScope('not_disabled')->findOrFail($id);
        } else {
            $dataset = Dataset::findOrFail($id);
        }

        return response($dataset->scraperKerndaten->xml)->header('content-type','text/xml');
    }
}

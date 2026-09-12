<?php

namespace App\Http\Controllers;

use App\CPV;
use App\Dataset;
use App\Http\Filters\DatasetFilter;
use Illuminate\Http\Request;
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
    public function index(DatasetFilter $filters, Request $request) {
        if_debug_mode_enable_query_log();

        // complex query necessary if user uses text search or sorts by offeror or contractor names
        $needsExtensiveQuery = $request->has('search') && $request->input('search') ||
            ($request->has('sort') && in_array($request->input('sort'),['offeror','-offeror','contractor','-contractor']));
        if ($needsExtensiveQuery) {
            $query = Dataset::indexQuery();
        } else {
            $query = Dataset::indexQuery(['joinOfferors' => false, 'joinContractors' => false]);
        }

        $query->filter($filters);

        if (!$filters->has('sort')) {
            // apply default sorting, item aktualisierungsdatum
            $query->orderBy('item_lastmod','desc');
        }

        $data       = $query->paginate(20);
        $totalItems = $data->total();

        if (count($data) > 0) {
            // now load the appropriate models for the view
            $items = Dataset::loadInOrder($data->pluck('id')->toArray())
                ->with('offeror')    // pre loads the main offeror "is_extra === 0"
                ->with('offerors')
                ->with('contractor') // pre loads the main contractor "is_extra === 0"
                ->with('contractors')
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

        // in case user filtered by cpv we want to show cpv description on page load
        $cpvName = null;
        if ($filters->has('cpv') && isset($appliedFilters['cpv'])) {
            $cpv = CPV::where('trimmed_code',rtrim($appliedFilters['cpv'],'*'))->first();
            if ($cpv) {
                $cpvName = $cpv->name;
            }
        }

        return view('public.datasets.index',compact('items','totalItems','filters','data','queryString','cpvName'));
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

<?php

namespace App\Http\Controllers;

use App\Contractor;
use App\Offeror;
use App\Organization;
use App\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laracasts\Utilities\JavaScript\JavaScriptFacade;

class PageController extends Controller
{
    public function frontpage() {

        $topOfferorsByCount = $this->fetchTopOfferors('count',10);
        $topOfferorsBySum = $this->fetchTopOfferors('sum',10);

        $topContractorsByCount = $this->fetchTopContractors('count',10);
        $topContractorsBySum = $this->fetchTopContractors('sum',10);

        return view('public.frontpage',compact('topOfferorsByCount','topOfferorsBySum','topContractorsByCount','topContractorsBySum'));
    }

    protected function fetchTopOfferors($type, $limit) {
        $res = Offeror::bigFishQuery($type)->limit($limit)->get();
        $ids = $res->pluck('organization_id')->toArray();      // has order
        $idsStr = join(',',$ids);
        $values = $res->keyBy('organization_id');  // key count value by org id

        // now load the appropriate models for the view
        $orgs = Organization::whereIn('id',$ids)
            ->orderByRaw(DB::raw("FIELD(id, $idsStr)"))->get();

        foreach($orgs as &$org) {
            if ($type == 'count') {
                $org->datasets_count = $values[$org->id]->datasets_count;
            }
            if ($type == 'sum') {
                $org->sum_total_val = $values[$org->id]->sum_total_val;
            }
        }

        return $orgs;
    }

    /**
     * @param $type String, "count" or "sum" (forwarded to bigFishQuery)
     * @param int $limit
     *
     * @return mixed
     */
    protected function fetchTopContractors($type, $limit = 10) {

        $res = Contractor::bigFishQuery($type)->limit($limit)->get();
        $ids = $res->pluck('organization_id')->toArray();      // has order
        $idsStr = join(',',$ids);
        $values = $res->keyBy('organization_id');  // key count value by org id
        // now load the appropriate models for the view
        $orgs = Organization::whereIn('id',$ids)
            ->orderByRaw(DB::raw("FIELD(id, $idsStr)"))->get();

        foreach($orgs as &$org) {
            // write the datasets count value into the orgs entities as a 'dynamic' attribute

            if ($type == 'count') {
                $org->datasets_count = $values[$org->id]->datasets_count;
            }
            if ($type == 'sum') {
                $org->sum_total_val = $values[$org->id]->sum_total_val;
            }
        }
        return $orgs;
    }

    /**
     * @param $slug
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function page($slug) {
        $page = Page::findBySlugOrFail($slug);

        if (!$page->isPublic) {
            $this->authorize('show-page',$page);
        }

        return view('public.page', compact('page'));
    }

    /**
     * Handles request to pre-defined "reserved" routes, like about-us, impressum etc.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reserved(Request $request) {
        return $this->page($request->segment(1));
    }
}

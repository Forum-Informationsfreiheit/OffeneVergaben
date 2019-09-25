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

        $topOfferorsByCount = $this->fetchTopOfferorsByCountData();
        $topContractorsByCount = $this->fetchTopContractorsByCountData();

        return view('public.frontpage',compact('topOfferorsByCount','topContractorsByCount'));
    }

    protected function fetchTopOfferorsByCountData() {
        $res = Offeror::bigFishQuery()->limit(10)->get();
        $ids = $res->pluck('organization_id')->toArray();      // has order
        $idsStr = join(',',$ids);
        $values = $res->pluck('datasets_count','organization_id');  // key count value by org id

        // now load the appropriate models for the view
        $orgs = Organization::whereIn('id',$ids)
            ->orderByRaw(DB::raw("FIELD(id, $idsStr)"))->get();

        foreach($orgs as &$org) {
            // write the datasets count value into the orgs entities as a 'dynamic' attribute
            $org->datasets_count = $values[$org->id];
        }

        return $orgs;
    }

    protected function fetchTopContractorsByCountData() {
        $res = Contractor::bigFishQuery()->limit(10)->get();
        $ids = $res->pluck('organization_id')->toArray();      // has order
        $idsStr = join(',',$ids);
        $values = $res->pluck('datasets_count','organization_id');  // key count value by org id

        // now load the appropriate models for the view
        $orgs = Organization::whereIn('id',$ids)
            ->orderByRaw(DB::raw("FIELD(id, $idsStr)"))->get();

        foreach($orgs as &$org) {
            // write the datasets count value into the orgs entities as a 'dynamic' attribute
            $org->datasets_count = $values[$org->id];
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

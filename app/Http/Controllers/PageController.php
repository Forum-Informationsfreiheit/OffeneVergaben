<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Organization;
use App\Page;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function frontpage() {
        if_debug_mode_enable_query_log();
        $topOfferorsByCount = $this->fetchTopOfferors('count',10);
        $topOfferorsBySum = $this->fetchTopOfferors('sum',10);

        $topContractorsByCount = $this->fetchTopContractors('count',10);
        $topContractorsBySum = $this->fetchTopContractors('sum',10);

        $posts = $this->fetchLastPosts();

        return view('public.frontpage',compact('topOfferorsByCount','topOfferorsBySum','topContractorsByCount','topContractorsBySum','posts'));
    }

    public function searchResultsPage(Request $request) {
        $search = $request->input('suche');

        $search = trim($search);
        $tokens = explode(' ',$search);

        $organizations = [];
        $datasets = [];

        if ($search) {
            $organizations = Organization::searchNameQuery($tokens)->limit(101)->get();

            // 2020-04-16 changed Organization query to actually return the offeror/contractor count
            // need php to sort the result by the sum of these to count values
            $organizations = $organizations->sortByDesc(function($orga){
                return $orga->is_offeror + $orga->is_contractor;
            });

            $datasets = Dataset::searchTitleAndDescriptionQuery($tokens)->limit(100)->get();
        }

        $totalItems = count($organizations);

        return view('public.searchresults',compact('organizations','datasets','totalItems','tokens'));
    }

    protected function fetchTopOfferors($type, $limit) {
        // queries based on pre-calculated count/volume stats
        if ($type === 'count') {
            $orgs = Organization::query()
                ->select()
                ->addSelect(DB::raw('count_auftrag_offeror as datasets_count'))
                ->orderBy('datasets_count','desc')
                ->limit($limit)
                ->get();
        } else if ($type === 'sum') {
            $orgs = Organization::query()
                ->select()
                ->addSelect(DB::raw('val_total_auftrag_offeror as sum_total_val'))
                ->orderBy('sum_total_val','desc')
                ->limit($limit)
                ->get();
        }

        return $orgs;
    }

    /**
     * @param $type String, "count" or "sum"
     * @param int $limit
     *
     * @return mixed
     */
    protected function fetchTopContractors($type, $limit = 10) {
        // queries based on pre-calculated count/volume stats
        if ($type === 'count') {
            $orgs = Organization::query()
                ->select()
                ->addSelect(DB::raw('count_auftrag_contractor as datasets_count'))
                ->orderBy('datasets_count','desc')
                ->limit($limit)
                ->get();
        } else if ($type === 'sum') {
            $orgs = Organization::query()
                ->select()
                ->addSelect(DB::raw('val_total_auftrag_contractor as sum_total_val'))
                ->orderBy('sum_total_val','desc')
                ->limit($limit)
                ->get();
        }

        return $orgs;
    }

    protected function fetchLastPosts() {
        $query = Post::published()->orderBy('published_at','desc')->limit(3);

        $posts = $query->get();

        return $posts;
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
     * Those are virtually the same as other page routes but without the preceding "page" segment in the url
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reserved(Request $request) {
        return $this->page($request->segment(1));
    }
}

<?php

namespace App\Http\Controllers;

use App\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracasts\Utilities\JavaScript\JavaScriptFacade;

class PageController extends Controller
{
    public function frontpage() {

        return view('public.frontpage');
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

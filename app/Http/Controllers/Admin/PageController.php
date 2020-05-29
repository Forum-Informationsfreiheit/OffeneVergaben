<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Page;
use App\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;

class PageController extends Controller
{
    public function index() {
        $pages = Page::all();

        return view('admin.pages.index',compact('pages'));
    }

    public function create()
    {
        $this->authorize('update-pages');

        $page = null;

        return view('admin.pages.create',compact('page'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('update-pages');

        $this->validate($request,[
            'title' => 'required|min:3|max:190',
        ]);

        // slug logic: don't change if manually set by user, but make sure the slug is unique.
        // Otherwise generate from title

        $page = new Page();
        $page->title = $request->input('title');
        $page->body = $request->input('body');
        $page->slug = $request->input('slug') ?
            Post::uniqueSlug($request->input('slug')) :
            Post::uniqueSlug(Str::slug($request->input('title')));
        $page->author_id = Auth::user()->id;

        $page->save();

        Flash::success(__("admin.pages.created"));

        return redirect(route('admin::pages'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $this->authorize('update-pages');

        $page = Page::findOrFail($id);

        return view('admin.pages.edit',compact('page'));
    }

    public function update(Request $request) {
        $this->authorize('update-pages');

        $page = Page::findOrFail($request->input('id'));

        $this->validate($request,[
            'title'       => 'required|max:190',
        ]);

        $page->title = $request->input('title');
        $page->body  = $request->input('body');

        // slug check (slug changed?)
        if ($page->slug !== $request->input('slug')) {
            // slug was cleared ? --> generate new
            if (!$request->input('slug')) {
                $page->slug = Post::uniqueSlug(Str::slug($request->input('title')));
            } else {
                // changed manually ?
                $page->slug = Post::uniqueSlug($request->input('slug'));
            }
        }

        $page->save();

        Flash::success(__("admin.pages.updated"));

        return redirect(route('admin::pages'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->authorize('update-pages');

        $id = $request->input('id');

        Page::findOrFail($id);

        // just do it right here:
        DB::table('pages')->where('id', $id)->delete();

        Flash::success(__("admin.pages.deleted"));

        return redirect(route('admin::pages'));
    }

    public function publish(Request $request) {
        $this->authorize('update-pages');

        $page = Page::findOrFail($request->input('id'));
        $page->published_at = $request->input('mode') === 'publish' ? Carbon::now() : null;
        $page->save();

        return redirect(route('admin::pages'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Post;
use App\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;
use Laracasts\Utilities\JavaScript\JavaScriptFacade;

class PostController extends Controller
{
    public function index() {
        $posts = Post::all();

        return view('admin.posts.index',compact('posts'));
    }

    public function create()
    {
        $this->authorize('update-posts');

        $post = null;
        $tags = Tag::all();

        JavaScriptFacade::put([
            'data' => [
                'tags' => $tags,
                'selectedTags' => [],
            ]
        ]);

        return view('admin.posts.create',compact('post'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('update-posts');

        $this->validate($request,[
            'title' => 'required|min:3|max:190',
        ]);

        // slug logic: don't change if manually set by user, but make sure the slug is unique.
        // Otherwise generate from title

        $post = new Post();
        $post->title = $request->input('title');
        $post->summary = $request->input('summary') ? $request->input('summary') : '';
        $post->body = $request->input('body') ? $request->input('body') : '';
        $post->slug = $request->input('slug') ?
            Post::uniqueSlug($request->input('slug')) :
            Post::uniqueSlug(Str::slug($request->input('title')));
        $post->author_id = Auth::user()->id;

        if ($request->has('image_filepath') && $request->input('image_filepath')) {
            $path = $request->input('image_filepath');

            // need to manipulate the image path, we are given the full url, but we need a relative path
            // starting from within the public directory (does not start with public/f1/f2... but with f1/f2)
            if (Str::startsWith($path,url('/'))) {
                $path = ltrim($path,url('/'));
            }

            $post->image_filepath = dirname($path);
            $post->image_filename = basename($path);
        }

        $post->save();

        $post->tags()->sync($request->has('tags') ? $request->input('tags') : []);

        Flash::success(__("admin.posts.created"));

        return redirect(route('admin::posts'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $this->authorize('update-posts');

        $post = Post::findOrFail($id);
        $tags = Tag::all();

        JavaScriptFacade::put([
            'data' => [
                'tags' => $tags,
                'selectedTags' => $post->tags,
            ]
        ]);

        return view('admin.posts.edit',compact('post'));
    }

    public function update(Request $request) {
        $this->authorize('update-posts');

        $post = Post::findOrFail($request->input('id'));

        $this->validate($request,[
            'title'       => 'required|max:190',
        ]);

        $post->title = $request->input('title');
        $post->summary = $request->input('summary') ? $request->input('summary') : '';
        $post->body  = $request->input('body') ? $request->input('body') : '';

        // slug check (slug changed?)
        if ($post->slug !== $request->input('slug')) {
            // slug was cleared ? --> generate new
            if (!$request->input('slug')) {
                $post->slug = Post::uniqueSlug(Str::slug($request->input('title')));
            } else {
                // changed manually ?
                $post->slug = Post::uniqueSlug($request->input('slug'));
            }
        }

        if ($request->has('image_filepath') && $request->input('image_filepath')) {
            $path = $request->input('image_filepath');

            // need to manipulate the image path, we are given the full url, but we need a relative path
            // starting from within the public directory (does not start with public/f1/f2... but with f1/f2)
            if (Str::startsWith($path,url('/'))) {
                $path = ltrim($path,url('/'));
            }

            $post->image_filepath = dirname($path);
            $post->image_filename = basename($path);
        }

        $post->save();

        $post->tags()->sync($request->has('tags') ? $request->input('tags') : []);

        Flash::success(__("admin.posts.updated"));

        return redirect(route('admin::posts'));
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
        $this->authorize('update-posts');

        $id = $request->input('id');

        $post = Post::findOrFail($id);
        $title = $post->title;

        // just do it right here:
        DB::table('posts')->where('id', $id)->delete();

        Flash::success(__("admin.posts.deleted", [ 'title' => $title ]));

        return redirect(route('admin::posts'));
    }

    public function publish(Request $request) {
        $this->authorize('update-posts');

        $post = Post::findOrFail($request->input('id'));
        $post->published_at = $request->input('mode') === 'publish' ? Carbon::now() : null;
        $post->save();

        if ($post->published_at) {
            Flash::success(__("admin.posts.published"  , [ 'title' => $post->title ]));
        } else {
            Flash::success(__("admin.posts.unpublished", [ 'title' => $post->title ]));
        }

        return redirect(route('admin::posts'));
    }
}

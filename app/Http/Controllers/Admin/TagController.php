<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;

class TagController extends Controller
{
    public function index() {
        //$users = User::orderBy('role_id','desc')->orderBy('created_at','desc')->paginate(10);
        $tags = Tag::orderBy('name','asc')->get();

        return view('admin.tags.index',compact('tags'));
    }

    public function create()
    {
        $this->authorize('update-tags');

        $tag = null;

        return view('admin.tags.create',compact('tag'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('update-tags');

        $this->validate($request,[
            'name'       => 'required|unique:tags|max:190',
            'description'=> 'max:190',
        ]);

        Tag::create([
            'name' => $request->input('name'),
            'description' => $request->input('description') ? $request->input('description') : null
        ]);

        Flash::success(__("admin.tags.created"));

        return redirect(route('admin::tags'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $tag = Tag::findOrFail($id);

        $this->authorize('update-tags');

        return view('admin.tags.edit',compact('tag'));
    }

    public function update(Request $request) {
        $tag = Tag::findOrFail($request->input('id'));

        $this->authorize('update-tags');

        // changed ?
        if ($tag->name != $request->input('name')) {
            $this->validate($request,[
                'name'       => 'required|unique:tags|max:255',
            ]);

            $tag->update([
                'name' => $request->input('name'),
                'description' => $request->input('description') ? $request->input('description') : null
            ]);
        }

        Flash::success(__("admin.tags.updated"));

        return redirect(route('admin::tags'));
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
        $this->authorize('update-tags');

        $id = $request->input('id');

        Tag::findOrFail($id);

        // just do it right here:
        DB::table('tags')->where('id', $id)->delete();

        Flash::success(__("admin.tags.deleted"));

        return redirect(route('admin::tags'));
    }
}

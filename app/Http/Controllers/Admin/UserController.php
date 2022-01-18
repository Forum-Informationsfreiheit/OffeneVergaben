<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Laracasts\Flash\Flash;

class UserController extends Controller
{
    public function index() {
        $users = User::all();

        return view('admin.users.index',compact('users'));
    }

    public function create() {
        $this->authorize('create-user');

        $user  = null;
        $roles = Role::all();

        return view('admin.users.create',compact('roles','user'));
    }

    public function store(Request $request) {
        $this->authorize('create-user');

        $this->validate($request,[
            'name'       => 'required|max:100',
            'email'      => 'required|email|max:100|unique:users',
            'password'   => 'required|min:6|confirmed',   // password verification ?!
            'role'       => 'required',                   // could be validated against a valid role
        ]);

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->role_id = $request->input('role');

        $user->save();

        Flash::success(__("admin.users.updated"));

        return redirect(route('admin::users'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $user = User::findOrFail($id);

        $this->authorize('update-user', $user);

        $roles = $roles = Role::all();

        return view('admin.users.edit',compact('user','roles'));
    }

    /**
     * Update User
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request) {
        $user = User::findOrFail($request->input('id'));
        $this->authorize('update-user', $user);

        $rules = [
            'name'       => 'required|max:100',
            'email'      => 'required|email|max:100',
        ];

        if ($request->input('password')) {
            $rules['password'] = 'min:6|confirmed';
        }

        $this->validate($request,$rules);

        $user->email = $request->input('email');
        $user->name = $request->input('name');

        if ($request->input('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        if (Auth::user()->isAdmin()) {
            $user->role_id  = $request->input('role');
        }

        $user->save();

        Flash::success(__("admin.users.updated"));

        return redirect(route('admin::edit-user',$request->input('id')));
    }

    public function destroy(Request $request)
    {
        $this->authorize('delete-user');

        $id = $request->input('id');

        if ($id == "1") {
            Flash::warning(__("Unable to delete root user."));
            return redirect(route('admin::users'));
        }

        User::findOrFail($id);

        // just do it right here:
        DB::table('users')->where('id', $id)->delete();

        Flash::success(__("admin.users.deleted"));

        return redirect(route('admin::users'));
    }
}

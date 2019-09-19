<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Role;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        // if the user tried to access an admin page but failed to do so
        // the middleware put the requested page under 'url.failed_admin' in the session
        // afterwards it threw a HttpException with a 403 status code

        // if the user now decides he wants to login, because he manually clicked on the
        // login link, the session key 'url.failed_admin' is still set
        // and we put its value into the - by default checked session key -
        // in 'url.intended' which will always be the currently requested page
        // after that its just standard laravel takes care of the redirect

        if (session('url.failed_admin',null)) {
            session(['url.intended' => session('url.failed_admin')]);
        }

        return view('auth.login');
    }

    /**
     * Where to redirect users after login.
     */
    public function redirectTo() {
        if (Auth::user() && Auth::user()->role_id >= Role::MIN_ROLE_FOR_ADMIN_AREA_ACCESS) {
            return '/admin';
        }

        return '/';
    }
}

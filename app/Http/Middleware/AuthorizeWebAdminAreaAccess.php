<?php

namespace App\Http\Middleware;

use App\Role;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthorizeWebAdminAreaAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role_id < Role::MIN_ROLE_FOR_ADMIN_AREA_ACCESS ) {

            // write current url to session
            session([ 'url.failed_admin' => url()->current() ]);

            // don't give any info away if the user is not authorized
            // just pretend the requested url does not exist
            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}

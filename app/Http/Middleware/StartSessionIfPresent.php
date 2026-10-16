<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Session handling for the public (cacheable) routes.
 *
 * Anonymous visitors get no session and no cookies, so their responses can be cached. The session is only started
 * for requests that already carry a session cookie (set by /login), e.g. editors who preview disabled datasets.
 */
class StartSessionIfPresent
{
    public function handle($request, Closure $next)
    {
        if (!static::hasSessionCookie($request)) {
            return $next($request);
        }

        return (new Pipeline(app()))
            ->send($request)
            ->through([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
            ])
            ->then($next);
    }

    public static function hasSessionCookie($request): bool
    {
        return $request->cookies->has(config('session.cookie'));
    }
}

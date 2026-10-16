<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Marks responses of anonymous GET/HEAD requests as cacheable by shared caches (reverse proxy, CDN).
 *
 * Requests with a session cookie or a logged in user are never cacheable. A shared cache has to bypass the cache
 * for requests carrying the session cookie as well, since those responses can differ per user.
 *
 * Sends X-Accel-Expires as well, which nginx obeys before Cache-Control.
 *
 * Usage: 'cache.public' (ttl from config app.public_cache_ttl) or 'cache.public:600'
 */
class SetPublicCacheHeaders
{
    public function handle($request, Closure $next, $ttl = null)
    {
        $response = $next($request);

        $ttl = (int) ($ttl ?? config('app.public_cache_ttl'));

        if ($request->isMethodCacheable() && $response->isSuccessful()) {
            if ($ttl > 0 && !StartSessionIfPresent::hasSessionCookie($request) && !auth()->check()) {
                $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=' . $ttl);
                // nginx takes the lifetime from here (and strips the header), independent of its s-maxage support
                $response->headers->set('X-Accel-Expires', (string) $ttl);
            } else {
                $response->headers->set('Cache-Control', 'no-store, private');
            }
        }

        return $response;
    }
}

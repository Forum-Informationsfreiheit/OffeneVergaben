<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Support\PublicMessages;

/**
 * The public pages are cacheable: no session, cookies or CSRF token for anonymous visitors.
 */
class PublicCachingTest extends PublicTestCase
{
    public function cacheablePathProvider()
    {
        return [
            'frontpage'   => ['/'],
            'datasets'    => ['/aufträge'],
            'contractors' => ['/lieferanten'],
            'offerors'    => ['/auftraggeber'],
            'branchen'    => ['/branchen'],
            'posts'       => ['/neuigkeiten'],
        ];
    }

    /**
     * @dataProvider cacheablePathProvider
     */
    public function testAnonymousResponseIsCacheableWithoutCookiesOrToken($path)
    {
        $response = $this->get($path);

        $response->assertOk();
        $this->assertEmpty($response->headers->getCookies());
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('s-maxage=' . config('app.public_cache_ttl'), $response->headers->get('Cache-Control'));
        $this->assertSame((string) config('app.public_cache_ttl'), $response->headers->get('X-Accel-Expires'));
        $response->assertDontSee('csrf-token', false);
        $response->assertDontSee('name="_token"', false);
    }

    public function testResponseWithSessionCookieIsNotCacheable()
    {
        $response = $this->withUnencryptedCookie(config('session.cookie'), 'x')->get('/aufträge');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertNull($response->headers->get('X-Accel-Expires'));
    }

    public function testResponseForLoggedInUserIsNotCacheable()
    {
        $response = $this->actingAsRole(Role::EDITOR)->get('/aufträge');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function testCachingCanBeDisabled()
    {
        config(['app.public_cache_ttl' => 0]);

        $response = $this->get('/aufträge');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function testDownloadLinkCreationIsNeverCached()
    {
        $response = $this->get('/downloads/kerndaten_dump_daily');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function testLoginKeepsSessionAndCsrfToken()
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="_token"', false);
    }

    public function testMessagesOverviewListsAllMessages()
    {
        $response = $this->get('/test/messages');

        $response->assertOk();
        foreach (array_keys(PublicMessages::catalog()) as $key) {
            $response->assertSee($key);
        }
    }
}

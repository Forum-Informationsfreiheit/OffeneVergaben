<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;

class AccessTest extends AdminTestCase
{
    /**
     * Guests get a 404 for every admin route, so the admin area is not revealed. The requested URL
     * is remembered for the redirect after login.
     */
    #[DataProvider('adminRoutesProvider')]
    public function testGuestGetsNotFound($method, $uri)
    {
        $response = $this->call($method, $uri);

        $response->assertNotFound();
        $response->assertSessionHas('url.failed_admin');
    }

    #[DataProvider('rolesBelowEditorProvider')]
    public function testUsersBelowEditorGetNotFound($roleId)
    {
        $response = $this->actingAs($this->createUser($roleId))->get('/admin');

        $response->assertNotFound();
    }

    #[DataProvider('adminAreaRolesProvider')]
    public function testEditorsAndAdminsSeeTheDashboard($roleId)
    {
        $user = $this->createUser($roleId);

        foreach (['/admin', '/admin/dashboard'] as $uri) {
            $response = $this->actingAs($user)->get($uri);

            $response->assertStatus(200);
            $response->assertViewIs('admin.dashboard');
        }
    }

    public function testUserProfileRouteIsAStub()
    {
        $response = $this->actingAs($this->createEditor())->get('/admin/user/42');

        $response->assertStatus(200);
        $response->assertSee('User profile 42');
    }

    /**
     * TODO: cleanup — the GET .../delete/{id} routes in routes/web_admin.php point to controller
     * methods that don't exist (HTTP 500) and are not used anywhere; the admin forms use the
     * DELETE .../destroy routes instead. Remove the four routes, then remove markTestIncomplete()
     * here and the four .../delete/{id} entries from adminRoutesProvider() (without a route the
     * web middleware doesn't run, so url.failed_admin is not set).
     */
    #[DataProvider('deleteRoutesProvider')]
    public function testDeleteRoutesDoNotExist($uri)
    {
        $this->markTestIncomplete('GET .../delete/{id} routes point to missing controller methods (HTTP 500); remove them from routes/web_admin.php.');

        $response = $this->actingAs($this->createAdmin())->get($uri);

        $response->assertNotFound();
    }

    public static function adminRoutesProvider()
    {
        return [
            'GET /admin'                          => ['GET',    '/admin'],
            'GET /admin/dashboard'                => ['GET',    '/admin/dashboard'],
            'GET /admin/user/{id}'                => ['GET',    '/admin/user/1'],

            'GET /admin/users'                    => ['GET',    '/admin/users'],
            'GET /admin/users/create'             => ['GET',    '/admin/users/create'],
            'GET /admin/users/edit/{id}'          => ['GET',    '/admin/users/edit/1'],
            'GET /admin/users/delete/{id}'        => ['GET',    '/admin/users/delete/1'],
            'POST /admin/users/store'             => ['POST',   '/admin/users/store'],
            'PATCH /admin/users/update'           => ['PATCH',  '/admin/users/update'],
            'DELETE /admin/users/destroy'         => ['DELETE', '/admin/users/destroy'],

            'GET /admin/tags'                     => ['GET',    '/admin/tags'],
            'GET /admin/tags/create'              => ['GET',    '/admin/tags/create'],
            'GET /admin/tags/edit/{id}'           => ['GET',    '/admin/tags/edit/1'],
            'GET /admin/tags/delete/{id}'         => ['GET',    '/admin/tags/delete/1'],
            'POST /admin/tags/store'              => ['POST',   '/admin/tags/store'],
            'PATCH /admin/tags/update'            => ['PATCH',  '/admin/tags/update'],
            'DELETE /admin/tags/destroy'          => ['DELETE', '/admin/tags/destroy'],

            'GET /admin/pages'                    => ['GET',    '/admin/pages'],
            'GET /admin/pages/create'             => ['GET',    '/admin/pages/create'],
            'GET /admin/pages/edit/{id}'          => ['GET',    '/admin/pages/edit/1'],
            'GET /admin/pages/delete/{id}'        => ['GET',    '/admin/pages/delete/1'],
            'POST /admin/pages/store'             => ['POST',   '/admin/pages/store'],
            'PATCH /admin/pages/update'           => ['PATCH',  '/admin/pages/update'],
            'PATCH /admin/pages/publish'          => ['PATCH',  '/admin/pages/publish'],
            'DELETE /admin/pages/destroy'         => ['DELETE', '/admin/pages/destroy'],

            'GET /admin/posts'                    => ['GET',    '/admin/posts'],
            'GET /admin/posts/create'             => ['GET',    '/admin/posts/create'],
            'GET /admin/posts/edit/{id}'          => ['GET',    '/admin/posts/edit/1'],
            'GET /admin/posts/delete/{id}'        => ['GET',    '/admin/posts/delete/1'],
            'POST /admin/posts/store'             => ['POST',   '/admin/posts/store'],
            'PATCH /admin/posts/update'           => ['PATCH',  '/admin/posts/update'],
            'PATCH /admin/posts/publish'          => ['PATCH',  '/admin/posts/publish'],
            'DELETE /admin/posts/destroy'         => ['DELETE', '/admin/posts/destroy'],

            'GET /admin/datasets'                 => ['GET',    '/admin/datasets'],
            'PATCH /admin/datasets/disable'       => ['PATCH',  '/admin/datasets/disable'],

            'GET /admin/subscriptions'            => ['GET',    '/admin/subscriptions'],
            'PATCH /admin/subscriptions/resend-…' => ['PATCH',  '/admin/subscriptions/resend-verification-notification'],
            'DELETE /admin/subscriptions/destroy' => ['DELETE', '/admin/subscriptions/destroy'],

            'GET /admin/lfm'                      => ['GET',    '/admin/lfm'],
            'GET /admin/lfm/jsonitems'            => ['GET',    '/admin/lfm/jsonitems'],
            'POST /admin/lfm/upload'              => ['POST',   '/admin/lfm/upload'],
        ];
    }

    public static function rolesBelowEditorProvider()
    {
        return [
            'registered' => [Role::REGISTERED],
            'subscriber' => [Role::SUBSCRIBER],
        ];
    }

    public static function adminAreaRolesProvider()
    {
        return [
            'editor' => [Role::EDITOR],
            'admin'  => [Role::ADMIN],
        ];
    }

    public static function deleteRoutesProvider()
    {
        return [
            'users' => ['/admin/users/delete/1'],
            'tags'  => ['/admin/tags/delete/1'],
            'pages' => ['/admin/pages/delete/1'],
            'posts' => ['/admin/posts/delete/1'],
        ];
    }
}

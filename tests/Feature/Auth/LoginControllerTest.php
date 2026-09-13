<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Role;
use Tests\Feature\PublicTestCase;

class LoginControllerTest extends PublicTestCase
{
    // password of the users created by the UserFactory
    const PASSWORD = 'password';

    protected function setUp(): void
    {
        parent::setUp();

        // the login form is sent without CSRF token, independent of APP_ENV
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testLoginFormLoads()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * @dataProvider redirectAfterLoginProvider
     */
    public function testLoginRedirectsByRole($roleId, $target)
    {
        $user = $this->createUser($roleId);

        $response = $this->post(route('login'), ['email' => $user->email, 'password' => self::PASSWORD]);

        $response->assertRedirect($target);
        $this->assertAuthenticatedAs($user);
    }

    public function testWrongPasswordIsRejected()
    {
        $user = $this->createUser(Role::EDITOR);

        $response = $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'falsch']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * A guest who opened an admin URL got a 404 (AuthorizeWebAdminAreaAccess). After logging in through the
     * login form, the user is sent to that URL.
     */
    public function testLoginAfterFailedAdminAccessRedirectsToThatUrl()
    {
        $editor = $this->createUser(Role::EDITOR);

        $this->get('/admin/pages')->assertNotFound();
        $this->get(route('login'))->assertStatus(200);
        $response = $this->post(route('login'), ['email' => $editor->email, 'password' => self::PASSWORD]);

        $response->assertRedirect(url('/admin/pages'));
    }

    /**
     * TODO: bug — RedirectIfAuthenticated (guest middleware of the login form) redirects logged-in users to
     * /home, which has no route (404). Redirect to the targets of LoginController::redirectTo() instead, then
     * remove markTestIncomplete() here.
     */
    public function testLoggedInEditorOpeningLoginFormIsRedirectedToAdminArea()
    {
        $this->markTestIncomplete('RedirectIfAuthenticated redirects to /home, which does not exist.');

        $response = $this->actingAsRole(Role::EDITOR)->get(route('login'));

        $response->assertRedirect('/admin');
    }

    public function testLogout()
    {
        $response = $this->actingAsRole(Role::EDITOR)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function redirectAfterLoginProvider()
    {
        return [
            'registered' => [Role::REGISTERED, '/'],
            'subscriber' => [Role::SUBSCRIBER, '/'],
            'editor'     => [Role::EDITOR,     '/admin'],
            'admin'      => [Role::ADMIN,      '/admin'],
        ];
    }
}

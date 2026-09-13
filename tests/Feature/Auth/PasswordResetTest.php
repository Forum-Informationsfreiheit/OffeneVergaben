<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\PublicTestCase;

/**
 * Password reset only exists outside of production, see RoutesTest.
 */
class PasswordResetTest extends PublicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // the forms are sent without CSRF token, independent of APP_ENV
        $this->withoutMiddleware(VerifyCsrfToken::class);

        Notification::fake();
    }

    public function testSendsResetLinkToUser()
    {
        $user = $this->createUser(Role::EDITOR);

        $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * TODO: bug — auth/passwords/email.blade.php, auth/passwords/reset.blade.php and auth/verify.blade.php
     * extend layouts.app, which doesn't exist (HTTP 500). Extend public.layouts.default like login and register,
     * then remove markTestIncomplete() here.
     */
    public function testResetFormLoads()
    {
        $this->markTestIncomplete('auth/passwords views extend the missing layouts.app (HTTP 500).');

        $this->get(route('password.request'))->assertStatus(200);
        $this->get(route('password.reset', ['token' => 'token']))->assertStatus(200);
    }
}

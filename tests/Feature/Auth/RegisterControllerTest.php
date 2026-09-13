<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Role;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\PublicTestCase;

/**
 * Registration only exists outside of production, see RoutesTest.
 */
class RegisterControllerTest extends PublicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // the registration form is sent without CSRF token, independent of APP_ENV
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testRegistrationFormLoads()
    {
        $this->get(route('register'))->assertStatus(200);
    }

    public function testRegistrationCreatesRegisteredUserAndLogsIn()
    {
        $input = $this->registrationInput();

        $response = $this->post(route('register'), $input);

        // RegisterController redirects to /home, which has no route (DOCUMENTATION.md, known quirk 16)
        $response->assertRedirect();
        $user = User::where('email', $input['email'])->first();
        $this->assertNotNull($user);
        $this->assertSame('Neue Redakteurin', $user->name);
        $this->assertSame(Role::REGISTERED, $user->role_id);
        $this->assertTrue(Hash::check('geheim123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @dataProvider invalidRegistrationProvider
     */
    public function testRegistrationValidatesInput(array $input, $field)
    {
        $response = $this->from(route('register'))->post(route('register'), $this->registrationInput($input));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors($field);
        $this->assertGuest();
    }

    public function invalidRegistrationProvider()
    {
        return [
            'name missing'           => [['name' => ''], 'name'],
            'email invalid'          => [['email' => 'keine-email'], 'email'],
            'password too short'     => [['password' => 'kurz', 'password_confirmation' => 'kurz'], 'password'],
            'password not confirmed' => [['password_confirmation' => 'anders123'], 'password'],
        ];
    }

    public function testRegistrationRejectsKnownEmail()
    {
        $existing = $this->createUser(Role::REGISTERED);

        $response = $this->from(route('register'))
            ->post(route('register'), $this->registrationInput(['email' => $existing->email]));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function registrationInput(array $overrides = [])
    {
        return array_merge([
            'name'                  => 'Neue Redakteurin',
            'email'                 => 'neu-'.Str::lower(Str::random(8)).'@example.test',
            'password'              => 'geheim123',
            'password_confirmation' => 'geheim123',
        ], $overrides);
    }
}

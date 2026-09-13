<?php

namespace Tests\Feature\Api;

use App\Role;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests of the auth:api middleware on GET /api/user (routes/api.php).
 *
 * The api guard uses the "token" driver (config/auth.php): it reads the token from the api_token
 * query/form parameter or the "Authorization: Bearer" header and looks it up unhashed in
 * users.api_token.
 *
 * Like the admin tests, every test runs inside a transaction against the configured MySQL database.
 */
class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * TODO: the users table has no api_token column, so every request that sends a token fails with a
     * QueryException (HTTP 500) instead of authenticating or getting a 401. Either add the column
     * ($table->string('api_token', 80)->unique()->nullable(), plus 'api_token' in User::$hidden) or
     * remove the route and the api guard. Then remove the markTestIncomplete() calls using this message.
     */
    const MISSING_API_TOKEN_COLUMN = 'users.api_token does not exist, every request with a token fails with HTTP 500.';

    protected function setUp(): void
    {
        parent::setUp();

        // the roles table has no seeder, but users.role_id references it
        DB::table('roles')->insertOrIgnore(['id' => Role::DEFAULT_ROLE, 'name' => 'Registered']);
    }

    public function testGuestGetsUnauthorized()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertExactJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Without "Accept: application/json" the Authenticate middleware redirects to the login form.
     */
    public function testGuestWithoutJsonAcceptHeaderIsRedirectedToLogin()
    {
        $response = $this->get('/api/user');

        $response->assertRedirect(route('login'));
    }

    public function testUserAuthenticatedOnApiGuardGetsOwnUser()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function testResponseHidesPasswordAndRememberToken()
    {
        $response = $this->actingAs($this->createUser(), 'api')->getJson('/api/user');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json());
        $this->assertArrayNotHasKey('remember_token', $response->json());
    }

    /**
     * A session login (web guard, e.g. an editor in the admin area) doesn't count for the api guard.
     */
    public function testSessionLoginDoesNotAuthenticate()
    {
        $response = $this->actingAs($this->createUser())->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * An empty token must count as no token: ConvertEmptyStringsToNull turns ?api_token= into null,
     * and where('api_token', null) would match every user without a token.
     *
     * @dataProvider emptyTokenProvider
     */
    public function testEmptyTokenGetsUnauthorized($uri, $headers)
    {
        $this->createUser();

        $response = $this->withHeaders($headers)->getJson($uri);

        $response->assertStatus(401);
    }

    public function testBearerTokenAuthenticates()
    {
        $this->markTestIncomplete(self::MISSING_API_TOKEN_COLUMN);

        $user = $this->createUser(['api_token' => Str::random(60)]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $user->api_token])->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJson(['id' => $user->id, 'email' => $user->email]);
        $this->assertArrayNotHasKey('api_token', $response->json());
    }

    public function testQueryParameterTokenAuthenticates()
    {
        $this->markTestIncomplete(self::MISSING_API_TOKEN_COLUMN);

        $user = $this->createUser(['api_token' => Str::random(60)]);

        $response = $this->getJson('/api/user?api_token=' . $user->api_token);

        $response->assertStatus(200);
        $response->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function testUnknownTokenGetsUnauthorized()
    {
        $this->markTestIncomplete(self::MISSING_API_TOKEN_COLUMN);

        $this->createUser(['api_token' => Str::random(60)]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . Str::random(60)])->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function emptyTokenProvider()
    {
        return [
            'empty query parameter' => ['/api/user?api_token=', []],
            'empty bearer token'    => ['/api/user', ['Authorization' => 'Bearer ']],
        ];
    }

    protected function createUser(array $attributes = [])
    {
        return factory(User::class)->create($attributes);
    }
}

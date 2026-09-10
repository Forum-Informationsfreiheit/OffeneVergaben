<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Base class for the tests of routes/web_admin.php.
 *
 * The tests run against the configured MySQL database (locally: the dev database). Every test runs
 * inside a transaction that is rolled back afterwards, so no test data is left behind.
 * Do NOT switch to RefreshDatabase: it drops every table of that database.
 */
abstract class AdminTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // form submissions are sent without CSRF token, independent of APP_ENV
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // the roles table has no seeder, but users.role_id references it
        DB::table('roles')->insertOrIgnore([
            ['id' => Role::REGISTERED, 'name' => 'Registered'],
            ['id' => Role::SUBSCRIBER, 'name' => 'Subscriber'],
            ['id' => Role::EDITOR,     'name' => 'Editor'],
            ['id' => Role::ADMIN,      'name' => 'Admin'],
        ]);

        // The user with ID 1 passes every gate (Gate::before). Make sure it exists, so users created
        // by the tests never get this ID and are checked like regular users.
        if (!User::find(1)) {
            User::forceCreate([
                'id'       => 1,
                'name'     => 'Root',
                'email'    => 'root@example.test',
                'password' => 'root',
                'role_id'  => Role::ADMIN,
            ]);
        }
    }

    protected function createUser($roleId)
    {
        return factory(User::class)->create(['role_id' => $roleId]);
    }

    protected function createEditor()
    {
        return $this->createUser(Role::EDITOR);
    }

    protected function createAdmin()
    {
        return $this->createUser(Role::ADMIN);
    }
}

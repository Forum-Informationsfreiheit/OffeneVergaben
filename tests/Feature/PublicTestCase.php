<?php

namespace Tests\Feature;

use App\Role;

/**
 * Base class for the tests of the public routes in routes/web.php.
 */
abstract class PublicTestCase extends DatabaseTestCase
{
    /**
     * Users who must not see unpublished posts and pages or disabled datasets. null is a guest.
     */
    public function visitorProvider()
    {
        return [
            'guest'      => [null],
            'registered' => [Role::REGISTERED],
            'subscriber' => [Role::SUBSCRIBER],
        ];
    }

    /**
     * Logs in a new user with the given role. For null the requests are sent as guest.
     */
    protected function actingAsRole($roleId)
    {
        return $roleId ? $this->actingAs($this->createUser($roleId)) : $this;
    }
}

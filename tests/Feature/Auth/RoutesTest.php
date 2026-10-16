<?php

namespace Tests\Feature\Auth;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * routes/web.php registers registration and password reset only outside of production.
 */
class RoutesTest extends TestCase
{
    const CLOSED_IN_PRODUCTION = ['register', 'password.request', 'password.email', 'password.reset', 'password.update'];

    public function testProductionOnlyHasLoginAndLogout()
    {
        $routes = $this->webRoutesIn('production');

        $this->assertNotNull($routes->getByName('login'));
        $this->assertNotNull($routes->getByName('logout'));
        // TODO: verification is disabled everywhere today; guard against it being enabled in production
        foreach (array_merge(self::CLOSED_IN_PRODUCTION, ['verification.notice']) as $name) {
            $this->assertNull($routes->getByName($name), "Route $name must not exist in production.");
        }
    }

    public function testOtherEnvironmentsAlsoHaveRegistrationAndPasswordReset()
    {
        $routes = $this->webRoutesIn('local');

        foreach (self::CLOSED_IN_PRODUCTION as $name) {
            $this->assertNotNull($routes->getByName($name), "Route $name is missing outside of production.");
        }
    }

    /**
     * Loads routes/web.php into a new router, as the app does when it boots in the given environment.
     */
    private function webRoutesIn($environment)
    {
        $this->app['env'] = $environment;

        $router = new Router($this->app['events'], $this->app);
        Route::swap($router);

        require base_path('routes/web.php');

        $routes = $router->getRoutes();
        $routes->refreshNameLookups();

        return $routes;
    }
}

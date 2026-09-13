<?php

namespace Tests\Feature;

use App\Role;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Mockery;

class ExceptionHandlerTest extends DatabaseTestCase
{
    /**
     * Reported exceptions are logged with the url, the input without passwords and the id of the logged in user.
     */
    public function testReportLogsRequestContextWithoutPasswords()
    {
        $user = $this->createUser(Role::EDITOR);
        $this->actingAs($user);
        $this->app->instance('request', Request::create('/login', 'POST', [
            'email'                 => 'redaktion@example.test',
            'password'              => 'geheim',
            'password_confirmation' => 'geheim',
        ]));
        Facade::clearResolvedInstance('request');
        $exception = new \RuntimeException('Testfehler');

        Log::shouldReceive('error')->once()->with('Testfehler', Mockery::on(function ($context) use ($user, $exception) {
            return $context === [
                'url'       => 'http://localhost/login',
                'input'     => ['email' => 'redaktion@example.test'],
                'userId'    => $user->id,
                'exception' => $exception,
            ];
        }));

        $this->app->make(ExceptionHandler::class)->report($exception);
    }
}

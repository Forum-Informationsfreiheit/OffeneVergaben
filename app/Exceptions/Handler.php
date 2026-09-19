<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the default context variables for logging.
     *
     * Override the default context with more verbose information.
     *
     * @return array
     */
    protected function context()
    {
        try {
            return array_filter([
                'url' => Request::fullUrl(),
                'input' => Request::except(['password', 'password_confirmation']),
                'userId' => Auth::id()
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }
}

<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public pages and subscription routes are in public.php (no session for anonymous visitors)

// Auth routes, only /login is open, others (register,passwort reset and email verification are not)
if (App::environment('production')) {
    Auth::routes(['register' => false, 'reset' => false, 'verify' => false]);
} else {
    Auth::routes();
}

// TEST routes, don't run in production
Route::group(['prefix' => 'test'], function () {
    if (App::environment('production')) {
        return;
    }

    // overview of all user facing messages of the public routes and where they are shown
    Route::get('messages', function () {
        $validationMessages = [];
        foreach ([
            'title'   => ['required' => ['required', []], 'max' => ['max.string', ['max' => 190]]],
            'email'   => ['required' => ['required', []], 'email' => ['email', []]],
            'query'   => ['required' => ['required', []]],
        ] as $field => $rules) {
            foreach ($rules as $rule => [$line, $replace]) {
                $validationMessages[] = [$field, $rule, trans('validation.' . $line, $replace + ['attribute' => trans('validation.attributes.' . $field) === 'validation.attributes.' . $field ? $field : trans('validation.attributes.' . $field)])];
            }
        }

        return view('test.messages', [
            'catalog' => \App\Support\PublicMessages::catalog(),
            'validationMessages' => $validationMessages,
        ]);
    });
});


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

Route::get('/', 'PageController@frontpage');

Route::get('/aufträge','DatasetController@index')->name('public::auftraege');
Route::get('/aufträge/{id}','DatasetController@show')->name('public::auftrag');
Route::get('/aufträge/{id}/xml','DatasetController@showXml')->name('public::auftragsxml');

Route::get('/lieferanten','ContractorController@index')->name('public::lieferanten');
Route::get('/lieferanten/{id}','ContractorController@show')->name('public::lieferant');

Route::get('/auftraggeber','OfferorController@index')->name('public::auftraggeber');
Route::get('/auftraggeber/{id}','OfferorController@show')->name('public::show-auftraggeber');

Route::get('/branchen','CpvController@index')->name('public::branchen');
Route::get('/branchen/search', 'CpvController@ajaxSearch')->name('public::ajax-cpv-search');

Route::get('/suchen','PageController@searchResultsPage')->name('public::suchen');

Route::get('/downloads','DownloadController@index')->name('public::downloads');
Route::get('/downloads/{fileName}','DownloadController@downloadStaticFile')->name('public::download-static-file');

Route::get('/neuigkeiten', 'PostController@index')->name('public::posts');
Route::get('/neuigkeiten/{slug}', 'PostController@show')->name('public::show-post');

// reserved routes for dynamic page content, directly under domain (no other url prefix)
Route::get('/impressum',   'PageController@reserved');
Route::get('/datenschutz', 'PageController@reserved');
Route::get('/überuns',     'PageController@reserved');
Route::get('/page/{slug}', 'PageController@page')->name('public::show-page');

// Auth routes, only /login is open, others (register,passwort reset and email verification are not)
if (App::environment('production')) {
    Auth::routes(['register' => false, 'reset' => false, 'verify' => false]);
} else {
    Auth::routes();
}

// Subscription routes
// NOTE that the verification route needs to be signed as indicated by the middleware
Route::post('/subscribe',     'SubscriptionController@subscribe')->name('public::subscribe');
Route::get('/subscriptions/{id}/verify/{email}', 'SubscriptionController@verify')
    ->middleware('signed')
    ->name('public::verify-subscription');
Route::get('/subscriptions/{id}/cancel/{email}', 'SubscriptionController@cancel')
    ->middleware('signed')
    ->name('public::cancel-subscription');
Route::get('/subscriptions/{id}/unsubscribe/{email}', 'SubscriptionController@unsubscribe')
    ->middleware('signed')
    ->name('public::unsubscribe');


// TEST routes, don't run in production
Route::group(['prefix' => 'test'], function () {
    if (App::environment('production')) {
        return;
    }

    // ...
});


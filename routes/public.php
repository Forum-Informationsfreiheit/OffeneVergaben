<?php

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Registered with the "public" middleware group (see Kernel): no session, cookies or CSRF token for anonymous
| visitors. Never use session state (flash messages, old input, $errors, csrf_token()) in these routes or in
| the views they render, answer actions with the response itself instead.
|
| Auth and admin routes live in web.php / web_admin.php and keep the full "web" group.
|
*/

// Cacheable pages (shared cache for anonymous GET requests only, see SetPublicCacheHeaders)
Route::middleware('cache.public')->group(function () {
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

    Route::get('/neuigkeiten', 'PostController@index')->name('public::posts');
    Route::get('/neuigkeiten/{slug}', 'PostController@show')->name('public::show-post');

    // reserved routes for dynamic page content, directly under domain (no other url prefix)
    Route::get('/impressum',   'PageController@reserved');
    Route::get('/datenschutz', 'PageController@reserved');
    Route::get('/überuns',     'PageController@reserved');
    Route::get('/frequently-asked-questions',  'PageController@reserved');
    Route::get('/faq',function() { return redirect('/frequently-asked-questions'); });
    Route::get('/page/{slug}', 'PageController@page')->name('public::show-page');
});

// Never cached: creates a temporary symlink on every request
Route::get('/downloads/{fileName}','DownloadController@downloadStaticFile')
    ->middleware('cache.headers:no_store;private')
    ->name('public::download-static-file');

// Subscription routes, never cached.
// The subscribe form is sent via fetch and answered with JSON, there is no CSRF token on the cached pages:
// the honeypot, the double opt-in and the throttle protect the endpoint.
// NOTE that the other routes need to be signed as indicated by the middleware
Route::middleware('cache.headers:no_store;private')->group(function () {
    Route::post('/subscribe', 'SubscriptionController@subscribe')
        ->middleware('throttle:10,1')
        ->name('public::subscribe');
    Route::get('/subscriptions/{id}/verify/{email}', 'SubscriptionController@verify')
        ->middleware('signed')
        ->name('public::verify-subscription');
    Route::get('/subscriptions/{id}/cancel/{email}', 'SubscriptionController@cancel')
        ->middleware('signed')
        ->name('public::cancel-subscription');
    Route::get('/subscriptions/{id}/unsubscribe/{email}', 'SubscriptionController@unsubscribe')
        ->middleware('signed')
        ->name('public::unsubscribe');
});

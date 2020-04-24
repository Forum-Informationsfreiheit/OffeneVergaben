<?php

Route::get('/', function () {
    return view('admin.dashboard');
})->name('admin::home');

Route::get('/dashboard', function () {
    return view('admin.dashboard');
})->name('admin::dashboard');

Route::get('/user/{id}', function ($id) {
    return "User profile $id";
});

Route::get   ('/users',        'UserController@index')->name('admin::users');
Route::get   ('/users/create', 'UserController@create')->name('admin::create-user');
Route::get   ('/users/edit/{id}', 'UserController@edit')->name('admin::edit-user');
Route::get   ('/users/delete/{id}', 'UserController@delete')->name('admin::delete-user');
Route::post  ('/users/store',  'UserController@store')->name('admin::store-user');
Route::patch ('/users/update',  'UserController@update')->name('admin::update-user');
Route::delete('/users/destroy','UserController@destroy')->name('admin::destroy-user');

Route::get   ('/tags',        'TagController@index')->name('admin::tags');
Route::get   ('/tags/create', 'TagController@create')->name('admin::create-tag');
Route::get   ('/tags/edit/{id}', 'TagController@edit')->name('admin::edit-tag');
Route::get   ('/tags/delete/{id}', 'TagController@delete')->name('admin::delete-tag');
Route::post  ('/tags/store',  'TagController@store')->name('admin::store-tag');
Route::patch ('/tags/update',  'TagController@update')->name('admin::update-tag');
Route::delete('/tags/destroy','TagController@destroy')->name('admin::destroy-tag');

Route::get   ('/pages',        'PageController@index')->name('admin::pages');
Route::get   ('/pages/create', 'PageController@create')->name('admin::create-page');
Route::get   ('/pages/edit/{id}', 'PageController@edit')->name('admin::edit-page');
Route::get   ('/pages/delete/{id}', 'PageController@delete')->name('admin::delete-page');
Route::post  ('/pages/store',  'PageController@store')->name('admin::store-page');
Route::patch ('/pages/update',  'PageController@update')->name('admin::update-page');
Route::patch ('/pages/publish','PageController@publish')->name('admin::publish-page');
Route::delete('/pages/destroy','PageController@destroy')->name('admin::destroy-page');

Route::get   ('/posts',        'PostController@index')->name('admin::posts');
Route::get   ('/posts/create', 'PostController@create')->name('admin::create-post');
Route::get   ('/posts/edit/{id}', 'PostController@edit')->name('admin::edit-post');
Route::get   ('/posts/delete/{id}', 'PostController@delete')->name('admin::delete-post');
Route::post  ('/posts/store',  'PostController@store')->name('admin::store-post');
Route::patch ('/posts/update',  'PostController@update')->name('admin::update-post');
Route::patch ('/posts/publish','PostController@publish')->name('admin::publish-post');
Route::delete('/posts/destroy','PostController@destroy')->name('admin::destroy-post');

Route::get   ('/datasets',         'DatasetController@index')->name('admin::datasets');
Route::patch ('/datasets/disable', 'DatasetController@disable')->name('admin::disable-dataset');

Route::get   ('/subscriptions',    'SubscriptionController@index')->name('admin::subscriptions');
Route::patch ('/subscriptions/resend-verification-notification', 'SubscriptionController@resendVerificationNotification')->name('admin::resend-subscription-verification-notification');
Route::delete('/subscriptions/destroy','SubscriptionController@destroy')->name('admin::destroy-subscription');
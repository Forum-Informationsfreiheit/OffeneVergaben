<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set default String Length to prevent
        // utf8_mb4 problems with older mysql versions
        // @see https://laravel-news.com/laravel-5-4-key-too-long-error
        Schema::defaultStringLength(191);
    }
}

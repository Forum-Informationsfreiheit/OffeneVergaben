<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

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

        // add @svg directive to blade templates
        // @see https://stackoverflow.com/a/43117258/718980
        Blade::directive('svg', function($arguments) {
            // Funky madness to accept multiple arguments into the directive
            list($path, $class) = array_pad(explode(',', trim($arguments, "() ")), 2, '');
            $path = trim($path, "' ");
            $class = trim($class, "' ");

            // Create the dom document as per the other answers
            $svg = new \DOMDocument();
            $svg->load(public_path($path));
            $svg->documentElement->setAttribute("class", $class);
            $output = $svg->saveXML($svg->documentElement);

            return $output;
        });
    }
}

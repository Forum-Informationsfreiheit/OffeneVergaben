const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

/*
mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css');
   */

// one for backend
mix.js('resources/js/app_admin.js', 'public/js');  // this contains the 'libraries ?!'
mix.js('resources/js/app_admin/sb-admin-2.js','public/js');

mix.sass('resources/sass/app_admin/app_admin.scss', 'public/css')
    .version();
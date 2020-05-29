/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./app_public/bootstrap');

// build up the global available namespace
window.__ives = { };

// build the custom __ives namespace
require('./app_public/util.js');

window.__ives.autocomplete = require('./app_public/autocomplete.js');
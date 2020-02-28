<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/4/18
 * Time: 1:37 AM.
 */

return [

    'routes' => [

        /**
         * Route prefix, example of route http://localhost/js/localizations.js.
         */
        'prefix' => env('LARAVEL_LOCALIZATION_PREFIX', '/js/localization.js'),

        /**
         * Route name, defaults to assets.lang.
         */
        'name' => env('LARAVEL_LOCALIZATION_ROUTE_NAME', 'assets.lang'),

        /**
         * Middleware used on localization routes.
         *
         * You can add more middleware with .env directive, example LARAVEL_LOCALIZATION_MIDDLEWARE=web,auth:api, etc.
         *
         * Don't use space in .env directive after ,
         */
        'middleware' => (env('LARAVEL_LOCALIZATION_MIDDLEWARE')) ?
            explode(',', env('LARAVEL_LOCALIZATION_MIDDLEWARE'))
            : [],

        /**
         * Should we enable public URL from which we can access translations.
         */
        'enable' => env('LARAVEL_LOCALIZATION_ROUTE_ENABLE', false),
    ],
    'events' => [

        /**
         * This package emits some events after it getters all translation messages.
         *
         * Here you can change channel on which events will broadcast
         */
        'channel' => env('LARAVEL_LOCALIZATION_EVENTS_CHANNEL', ''),
    ],
    'caches' => [

        /**
         * What cache driver do you want to use - more information: https://laravel.com/docs/5.6/cache#driver-prerequisites.
         */
        'driver' => 'file',

        /**
         * Key name of the cache entry for the localization array.
         */
        'key' => 'localization.array',

        /**
         * Timeout of the cached data in minutes - set to 0 to disable.
         */
        'timeout' => 60,
    ],
    'js' => [
        /**
         * Default locale for export.
         */
        'default_locale' => 'en',

        /**
         * root location to where JavaScript file will be exported.
         */
        'filepath' => resource_path('assets/js'),

        /**
         * File name for JavaScript file with exported messages.
         */
        'filename' => 'll_messages.js',
    ],
    'paths' => [

        /**
         * You can export more lang files then just files in resources/lang, for example.
         *
         * In you .env file just add:
         * LARAVEL_LOCALIZATION_LANG_DIRS=resources/lang,Modules/Blog/Resources/lang
         */
        'lang_dirs' => [resource_path('lang')],
    ],
    /**
     * You can customize the regexp for lang files to be able to exclude certain files.
     */
    'file_regexp' => [
        'php' => '/^.+\.php$/i',
        'json' => '/^.+\.json$/i',
    ],
    /**
     * This function will be called every time after export, it should be globally accessible function (eg. Laravel helper function)
     * and it should accept (string) argument.
     */
    'export_callback' => null,

];

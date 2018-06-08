<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/4/18
 * Time: 1:37 AM
 */

return [

    'routes' => [

        /**
         * Route prefix, example of route http://localhost/laravel-deploy/deploy?_token=#################
         *
         */
        'prefix'     => env( 'LARAVEL_LOCALIZATION_PREFIX', '/js/localization.js' ),

        /**
         * Middleware used on webhook routes, default middleware is KgBot\LaravelLocalization\Http\Middleware\IsTokenValid
         *
         * You can add more middleware with .env directive, example LARAVEL_DEPLOY_MIDDLEWARE=webhook,auth:api, etc.
         *
         * Don't use space in .env directive after ,
         */
        'middleware' => ( env( 'LARAVEL_LOCALIZATION_MIDDLEWARE' ) ) ?
            explode( ',', env( 'LARAVEL_LOCALIZATION_MIDDLEWARE' ) )
            : [],
    ],
    'events' => [

        /**
         * This package emits some events before and after it run's deployment script
         *
         * Here you can change channel on which events will be broadcast
         */
        'channel' => env( 'LARAVEL_LOCALIZATION_EVENTS_CHANNEL', '' ),
    ],
];
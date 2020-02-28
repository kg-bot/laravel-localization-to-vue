<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/4/18
 * Time: 12:18 AM.
 */
use KgBot\LaravelLocalization\Facades\ExportLocalizations;

if (config('laravel-localization.routes.enable')) {
    /*
     * Localization
     */
    Route::get(config('laravel-localization.routes.prefix'), 'KgBot\LaravelLocalization\Classes\ExportLocalizations@exportToArray')->name(config('laravel-localization.routes.name'))
         ->middleware(config('laravel-localization.routes.middleware'));
}

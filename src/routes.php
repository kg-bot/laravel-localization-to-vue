<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/4/18
 * Time: 12:18 AM
 */

use KgBot\LaravelLocalization\Facades\ExportLocalizations;

/**
 * Localization
 */
Route::get( config( 'laravel-localization.routes.prefix' ), function () {

    $strings = ExportLocalizations::export()->toArray();

    return response()->json( $strings );
} )->name( 'assets.lang' )->middleware( config( 'laravel-localization.routes.middleware' ) );

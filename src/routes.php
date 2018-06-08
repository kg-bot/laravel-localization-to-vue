<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/4/18
 * Time: 12:18 AM
 */

use ExportLocalization;

/**
 * Localization
 */
Route::get( config( 'laravel-localization.routes.prefix' ), function () {

    $strings = ExportLocalization::export();

    return response()->json( $strings );
} )->name( 'assets.lang' )->middleware( config( 'laravel-localization.routes.middleware' ) );

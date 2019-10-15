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
    Route::get(config('laravel-localization.routes.prefix'), function () {
        $strings = ExportLocalizations::export()->toArray();

        return response()->json($strings);
    })->name(config('laravel-localization.routes.name'))
         ->middleware(config('laravel-localization.routes.middleware'));
}

if (config('laravel-localization.web.enabled')) {
    Route::group(['middleware' => config('laravel-localization.web.middleware'), 'prefix' => config('laravel-localization.web.prefix')], function () {
        Route::get('', 'KgBot\LaravelLocalization\Http\Controllers\WebController@index')->name('laravel-localization.web');
        Route::get('get-group/{group}', 'KgBot\LaravelLocalization\Http\Controllers\WebController@openGroup')->name('laravel-localization.get-open-group');
        Route::post('select-group', 'KgBot\LaravelLocalization\Http\Controllers\WebController@openGroup')->name('laravel-localization.open-group');
        Route::post('create-group', 'KgBot\LaravelLocalization\Http\Controllers\WebController@postGroup')->name('laravel-localization.add-new-group');

        Route::post('locale', 'KgBot\LaravelLocalization\Http\Controllers\WebController@addNewLocale')->name('laravel-localization.add-new-locale');
        Route::post('delete-locale', 'KgBot\LaravelLocalization\Http\Controllers\WebController@deleteLocale')->name('laravel-localization.delete-locale');
        Route::post('post-key', 'KgBot\LaravelLocalization\Http\Controllers\WebController@postKey')->name('laravel-localization.post-key');
        Route::post('add-keys', 'KgBot\LaravelLocalization\Http\Controllers\WebController@addKeys')->name('laravel-localization.add-new-keys');
    });
}

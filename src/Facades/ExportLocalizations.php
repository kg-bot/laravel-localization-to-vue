<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/8/18
 * Time: 6:11 PM.
 */

namespace KgBot\LaravelLocalization\Facades;

use Illuminate\Support\Facades\Facade;

class ExportLocalizations extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'export-localization';
    }
}

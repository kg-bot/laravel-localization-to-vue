<?php

namespace KgBot\LaravelLocalization\Rules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Validation\Rule;
use KgBot\LaravelLocalization\Facades\ExportLocalizations;

class LocaleAlreadyExist implements Rule
{
    protected $files;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->files = new Filesystem();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ! in_array($value, ExportLocalizations::getLocales());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('laravel-localization::laravel-localization.validation.locale_already_exists');
    }
}

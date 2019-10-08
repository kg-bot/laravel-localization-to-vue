<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/8/18
 * Time: 6:12 PM.
 */

namespace KgBot\LaravelLocalization\Classes;

use Illuminate\Support\Facades\Cache;
use KgBot\LaravelLocalization\Events\LaravelLocalizationExported;

class ExportLocalizations implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $strings = [];

    /**
     * @var string
     */
    protected $phpRegex;

    /**
     * @var string
     */
    protected $jsonRegex;

    /**
     * @var string
     */
    protected $excludePath = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR;

    /**
     * @var string
     */
    protected $packageSeparator = '.';

    /**
     * ExportLocalizations constructor.
     *
     * @param string $phpRegex
     * @param string $jsonRegex
     */
    public function __construct($phpRegex, $jsonRegex)
    {
        $this->phpRegex = $phpRegex;
        $this->jsonRegex = $jsonRegex;
    }

    /**
     * Method to return generate array with contents of parsed language files.
     *
     * @return object
     */
    public function export()
    {
        // Check if value is cached and set array to cached version
        if (Cache::has(config('laravel-localization.caches.key'))) {
            $this->strings = Cache::get(config('laravel-localization.caches.key'));

            return $this;
        }

        foreach (config('laravel-localization.paths.lang_dirs') as $dir) {
            try {

                // Collect language files and build array with translations
                $files = $this->findLanguageFiles($dir);

                // Parse translations and create final array
                array_walk($files['lang'], [$this, 'parseLangFiles'], $dir);
                array_walk($files['vendor'], [$this, 'parseVendorFiles'], $dir);
                array_walk($files['json'], [$this, 'parseJsonFiles'], $dir);
            } catch (\Exception $exception) {
                \Log::critical('Can\'t read lang directory '.$dir.', error: '.$exception->getMessage());
            }
        }

        // Trigger event for final translated array
        event(new LaravelLocalizationExported($this->strings));

        // If timeout > 0 save array to cache
        if (config('laravel-localization.caches.timeout', 0) > 0) {
            Cache::store(config('laravel-localization.caches.driver', 'file'))
                 ->put(
                     config('laravel-localization.caches.key', 'localization.array'),
                     $this->strings,
                     config('laravel-localization.caches.timeout', 60)
                 );
        }

        return $this;
    }

    /**
     * Find available language files and parse them to array.
     *
     * @param string $path
     *
     * @return array
     */
    protected function findLanguageFiles($path)
    {
        // Loop through directories
        $dirIterator = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $recIterator = new \RecursiveIteratorIterator($dirIterator);

        // Fetch only php files - skip others
        $phpFiles = array_values(
            array_map('current',
                iterator_to_array(
                    new \RegexIterator($recIterator, $this->phpRegex, \RecursiveRegexIterator::GET_MATCH)
                )
            )
        );

        $jsonFiles = array_values(
            array_map('current',
                iterator_to_array(
                    new \RegexIterator($recIterator, $this->jsonRegex, \RecursiveRegexIterator::GET_MATCH)
                )
            )
        );

        $files = array_merge($phpFiles, $jsonFiles);

        // Sort array by filepath
        sort($files);

        // Remove full path from items
        array_walk($files, function (&$item) use ($path) {
            $item = str_replace($path, '', $item);
        });

        // Fetch non-vendor files from filtered php files
        $nonVendorFiles = array_filter($files, function ($file) {
            return strpos($file, $this->excludePath) === false && strpos($file, '.json') === false;
        });

        // Fetch vendor files from filtered php files
        $vendorFiles = array_filter(array_diff($files, $nonVendorFiles), function ($file) {
            return strpos($file, 'json') === false;
        });

        // Fetch .json files from filtered files
        $jsonFiles = array_filter($files, function ($file) {
            return strpos($file, '.json') !== false;
        });

        return [
            'lang'   => array_values($nonVendorFiles),
            'vendor' => array_values($vendorFiles),
            'json'   => array_values($jsonFiles),
        ];
    }

    /**
     * Method to return array for json serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->strings;
    }

    /**
     * Method to return array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->strings;
    }

    /**
     * If you need special format of array that's recognised by some npm localization packages as Lang.js
     * https://github.com/rmariuzzo/Lang.js use this method.
     *
     * @param array  $array
     * @param string $prefix
     *
     * @return array
     */
    public function toFlat($prefix = '.')
    {
        $results = [];
        $default_locale = config('laravel-localization.js.default_locale');
        $default_json_strings = null;

        foreach ($this->strings as $lang => $strings) {
            if ($lang !== 'json') {
                foreach ($strings as $lang_array => $lang_messages) {
                    $key = $lang.$prefix.$lang_array;
                    $results[$key] = $lang_messages;
                }
            } else {
                foreach ($strings as $json_lang => $json_strings) {
                    $key = $json_lang.$prefix.'__JSON__';
                    if (array_key_exists($key, $results)) {
                        $results[$key] = $json_strings;
                    } else {
                        $results[$key] = $json_strings;
                    }

                    // Pick only the first $json_strings
                    if (! $default_json_strings) {
                        $default_json_strings = $json_strings;
                    }
                }
            }
        }

        // Create a JSON key value pair for the default language
        $default_key = $default_locale.$prefix.'__JSON__';
        if (! array_key_exists($default_key, $results)) {
            $buffer = array_keys(
                $default_json_strings ? get_object_vars($default_json_strings) : []
            );

            $results[$default_key] = array_combine($buffer, $buffer);
        }

        return $results;
    }

    /**
     * Method to return array as collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toCollection()
    {
        return collect($this->strings);
    }

    /**
     * Method to parse language files.
     *
     * @param string $file
     */
    protected function parseLangFiles($file, $key, $dir)
    {
        // Base package name without file ending
        $packageName = basename($file, '.php');

        // Get package, language and file contents from language file
        // /<language_code>/(<package/)<filename>.php
        $language = explode(DIRECTORY_SEPARATOR, $file)[1];
        $fileContents = require $dir.DIRECTORY_SEPARATOR.$file;

        // Check if language already exists in array
        if (array_key_exists($language, $this->strings)) {
            if (array_key_exists($packageName, $this->strings[$language])) {
                $this->strings[$language][$packageName] =
                    array_replace_recursive((array) $this->strings[$language][$packageName], (array)
                    $fileContents);
            } else {
                $this->strings[$language][$packageName] = $fileContents;
            }
        } else {
            $this->strings[$language] = [
                $packageName => $fileContents,
            ];
        }
    }

    /**
     * Method to parse language files from vendor folder.
     *
     * @param string $file
     */
    protected function parseVendorFiles($file, $key, $dir)
    {
        // Base package name without file ending
        $packageName = basename($file, '.php');

        // Get package, language and file contents from language file
        // /vendor/<package>/<language_code>/<filename>.php
        $package = explode(DIRECTORY_SEPARATOR, $file)[2];
        $language = explode(DIRECTORY_SEPARATOR, $file)[3];
        $fileContents = require $dir.DIRECTORY_SEPARATOR.$file;

        // Check if language already exists in array
        if (array_key_exists($language, $this->strings)) {
            // Check if package already exists in language
            if (array_key_exists($package, $this->strings[$language])) {
                if (array_key_exists($packageName, $this->strings[$language][$package])) {
                    $this->strings[$language][$package][$packageName] =
                        array_replace_recursive((array) $this->strings[$language][$package][$packageName],
                            (array)
                            $fileContents);
                } else {
                    $this->strings[$language][$package][$packageName] = $fileContents;
                }
            } else {
                $this->strings[$language][$package] = [$packageName => $fileContents];
            }
        } else {
            $this->strings[$language] = [

                $package => [

                    $packageName => $fileContents,
                ],
            ];
        }
    }

    protected function parseJsonFiles($file, $key, $dir)
    {
        // Base package name without file ending
        $language = basename($file, '.json');

        // Get package, language and file contents from language file
        // /<language_code>/(<package/)<filename>.php
        $fileContents = json_decode(file_get_contents($dir.$file));

        // Check if language already exists in array
        if (array_key_exists('json', $this->strings)) {
            if (array_key_exists($language, $this->strings['json'])) {
                $this->strings['json'][$language] =
                    array_replace_recursive((array) $this->strings['json'][$language], (array) $fileContents);
            } else {
                $this->strings['json'][$language] = $fileContents;
            }
        } else {
            $this->strings['json'] = [

                $language => $fileContents,
            ];
        }
    }
}

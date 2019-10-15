<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/8/18
 * Time: 6:12 PM.
 */

namespace KgBot\LaravelLocalization\Classes;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use KgBot\LaravelLocalization\Events\CreatedNewLocaleKey;
use KgBot\LaravelLocalization\Events\DeletedLocalizationKey;
use KgBot\LaravelLocalization\Events\CreatedNewLocalizationKey;
use KgBot\LaravelLocalization\Events\CreatedNewLocalizationGroup;
use KgBot\LaravelLocalization\Events\LaravelLocalizationExported;

class ExportLocalizations implements \JsonSerializable
{
    /** @var array */
    protected $strings = [];

    /** @var string */
    protected $phpRegex;

    /** @var string */
    protected $jsonRegex;

    /** @var string */
    protected $excludePath = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR;

    /** @var string */
    protected $packageSeparator = '.';

    /** @var Filesystem */
    protected $files;

    /**
     * ExportLocalizations constructor.
     *
     * @param string $phpRegex
     * @param string $jsonRegex
     * @param Filesystem $filesystem
     */
    public function __construct($phpRegex, $jsonRegex)
    {
        $this->phpRegex = $phpRegex;
        $this->jsonRegex = $jsonRegex;
        $this->files = new Filesystem();
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

    /**
     * Parse json (files that match $this->jsonRegex regex) files.
     *
     * @param $file
     * @param $key
     * @param $dir
     */
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

    /**
     * Get all locales.
     *
     * @param null $dir
     * @param array $exclude
     * @return array
     */
    public function getLocales($dir = null, array $exclude = ['.', '..', 'vendor'])
    {
        $locales = [];
        $dir = $dir ?? resource_path('lang');

        foreach ($this->files->directories($dir) as $localeDir) {
            if (! in_array(($name = $this->files->name($localeDir)), $exclude)) {
                $locales[] = $name;
            }
        }
        sort($locales);

        return $locales;
    }

    /**
     * Get all localization groups without any content.
     *
     * @return array
     */
    public function getGroups()
    {
        $locales = $this->export()->toArray();
        $data = [];

        foreach ($locales as $lang => $groups) {
            if ($lang !== 'json') {
                foreach ($groups as $group => $keys) {
                    $data[] = $group;
                }
            } else {
                $data[] = '__JSON__';
            }
        }

        return array_unique($data);
    }

    /**
     * Return specified localization group with full content in array dot notation.
     *
     * @param $group
     * @return array
     */
    public function getGroup($group)
    {
        $translations = $this->export()->toArray();
        $data = [];

        if ($group === '__JSON__') {
            $group = 'json';
            if (isset($translations[$group])) {
                foreach ($translations[$group] as $locale => $translations) {
                    if (is_array($translations)) {
                        foreach ($translations as $key => $value) {

                            //$key = (is_array($value)) ? \Arr::dot($value) : $key;
                            if (isset($data[$key])) {
                                $data[$key][$locale] = $value;
                            } else {
                                $data[$key] = [

                                    $locale => $value,
                                ];
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($translations as $locale => $translation) {
                if (isset($translation[$group])) {
                    foreach ($translation[$group] as $key => $value) {

                        //$key = (is_array($value)) ? \Arr::dot($value) : $key;
                        if (isset($data[$key])) {
                            $data[$key][$locale] = \Arr::dot($translation[$group]);
                        } else {
                            $data[$key] = [

                                $locale => \Arr::dot($translation[$group]),
                            ];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Save new translation to localization group and key.
     *
     * @param $group_name
     * @param $key
     * @param $locale
     * @param $value
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function writeValue($group_name, $key, $locale, $value)
    {
        $groups = $this->getGroups();
        $translations = $this->export()->toArray();

        if (in_array($group_name, array_values($groups))) {
            if ($group_name === '__JSON__') {
                $group_name = 'json';
                if (! isset($translations[$group_name][$locale])) {
                    $translations[$group_name] = [

                        $locale => [

                            $key => $value,
                        ],
                    ];
                } else {
                    $translations[$group_name][$locale][$key] = $value;
                }

                $content = $translations[$group_name][$locale];
                $isJson = true;
            } else {
                if (! isset($translations[$locale][$group_name])) {
                    $translations[$locale] = [

                        $group_name => [

                            $key => $value,
                        ],
                    ];
                } else {
                    $translations[$locale][$group_name][$key] = $value;
                }

                $content = $translations[$locale][$group_name];
            }

            $this->writeGroup($content, $locale, $group_name, $isJson ?? false);

            $this->clearCache();

            return true;
        }
    }

    /**
     * Handle writing localization group to file(s).
     *
     * @param $content
     * @param $locale
     * @param $group_name
     * @param bool $isJson
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function writeGroup($content, $locale, $group_name, $isJson = false)
    {
        $output = $isJson === false ? "<?php\n\nreturn ".var_export($content, true).';'.\PHP_EOL : json_encode($content).\PHP_EOL;

        foreach (Config::get('laravel-localization.paths.lang_dirs') as $dir) {
            $path = rtrim($dir)."/{$locale}".($isJson === false ? "/{$group_name}.php" : '.json');

            if (! $this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0777, true);
            }
            $this->files->put($path, $output);
        }

        $this->clearCache();
    }

    /**
     * Save new key(s) in translation group file.
     *
     * @param $group_name
     * @param $keys
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function writeKeys($group_name, $keys)
    {
        $languages = $this->getLocales();

        foreach ($languages as $language) {
            $translations = $this->export()->toArray();

            if ($group_name === '__JSON__') {
                $group_name = 'json';
                if (! isset($translations[$group_name][$language])) {
                    $translations[$group_name] = [

                        $language => [],
                    ];
                }

                foreach ($keys as $key) {
                    $key = trim($key);
                    $translations[$group_name][$language][$key] = '';
                }

                $content = $translations[$group_name][$language];
                $isJson = true;
            } else {
                if (! isset($translations[$language][$group_name])) {
                    $translations[$language] = [

                        $group_name => [],
                    ];
                }

                foreach ($keys as $key) {
                    $key = trim($key);
                    $translations[$language][$group_name][$key] = '';
                }

                $content = $translations[$language][$group_name];
            }

            $this->writeGroup($content, $language, $group_name, $isJson ?? false);
            $this->clearCache();
        }

        event(new CreatedNewLocalizationKey($group_name, $keys));

        return true;
    }

    /**
     * Create new translation group.
     *
     * @param $group_name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createNewGroup($group_name)
    {
        $languages = $this->getLocales();

        foreach ($languages as $language) {
            $this->writeGroup([], $language, $group_name);
        }

        event(new CreatedNewLocalizationGroup($group_name));

        $this->clearCache();

        return true;
    }

    /**
     * Create new locale.
     *
     * @param string $locale
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createNewLocale($locale)
    {
        foreach (Config::get('laravel-localization.paths.lang_dirs') as $dir) {
            $locale = explode('/', $locale)[0];
            $path = rtrim($dir).'/'.$locale;
            if (! $this->files->exists($path)) {
                $this->files->makeDirectory($path, 0777, true);
                $this->files->put($path.'.json', json_encode([], JSON_FORCE_OBJECT).\PHP_EOL);
            }
        }

        event(new CreatedNewLocaleKey($locale));

        $this->clearCache();

        return true;
    }

    /**
     * Delete existing locale.
     *
     * @param array $locales
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteLocales($locales)
    {
        foreach ($locales as $locale => $value) {
            foreach (Config::get('laravel-localization.paths.lang_dirs') as $dir) {
                $locale = explode('/', $locale)[0];
                $path = rtrim($dir).'/'.$locale;
                $this->files->deleteDirectory($path);
                $this->files->delete([$path.'.json']);
            }

            event(new DeletedLocalizationKey($locale));
        }

        $this->clearCache();

        return true;
    }

    /**
     * Clear laravel-localization cache if it is enabled.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function clearCache()
    {
        if (config('laravel-localization.caches.timeout', 0) > 0) {
            $store = Cache::store(config('laravel-localization.caches.driver', 'file'));
            if ($store->has(config('laravel-localization.caches.key'))) {
                $store->forget(config('laravel-localization.caches.key'));
            }
        }
    }
}

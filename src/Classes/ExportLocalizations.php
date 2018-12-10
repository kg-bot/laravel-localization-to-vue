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
    protected $phpRegex = '/^.+\.php|.json$/i';

    /**
     * @var string
     */
    protected $jsonRegex = '/^.+\.json$/i';

    /**
     * @var string
     */
    protected $excludePath = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;

    /**
     * @var string
     */
    protected $packageSeparator = '.';

    /**
     * Method to return generate array with contents of parsed language files.
     *
     * @return object
     */
    public function export()
    {
        // Check if value is cached and set array to cached version
        if ( Cache::has( config( 'laravel-localization.caches.key' ) ) ) {
            $this->strings = Cache::get( config( 'laravel-localization.caches.key' ) );

            return $this;
        }

        // Collect language files and build array with translations
        $files = $this->findLanguageFiles( resource_path( 'lang' ) );

        // Parse translations and create final array
        array_walk( $files[ 'lang' ], [ $this, 'parseLangFiles' ] );
        array_walk( $files[ 'vendor' ], [ $this, 'parseVendorFiles' ] );
        array_walk( $files[ 'json' ], [ $this, 'parseJsonFiles' ] );

        // Trigger event for final translated array
        event( new LaravelLocalizationExported( $this->strings ) );

        // If timeout > 0 save array to cache
        if ( config( 'laravel-localization.caches.timeout', 0 ) > 0 ) {
            Cache::store( config( 'laravel-localization.caches.driver', 'file' ) )
                 ->put(
                     config( 'laravel-localization.caches.key', 'localization.array' ),
                     $this->strings,
                     config( 'laravel-localization.caches.timeout', 60 )
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
    protected function findLanguageFiles( $path )
    {
        // Loop through directories
        $dirIterator = new \RecursiveDirectoryIterator( $path, \RecursiveDirectoryIterator::SKIP_DOTS );
        $recIterator = new \RecursiveIteratorIterator( $dirIterator );

        // Fetch only php files - skip others
        $phpFiles = array_values(
            array_map( 'current',
                iterator_to_array(
                    new \RegexIterator( $recIterator, $this->phpRegex, \RecursiveRegexIterator::GET_MATCH )
                )
            )
        );


        // Sort array by filepath
        sort( $phpFiles );

        // Remove full path from items
        array_walk( $phpFiles, function ( &$item ) {
            $item = str_replace( resource_path( 'lang' ), '', $item );
        } );

        // Fetch non-vendor files from filtered php files
        $nonVendorFiles = array_filter( $phpFiles, function ( $file ) {
            return strpos( $file, $this->excludePath ) === false;
        } );

        // Fetch vendor files from filtered php files
        $vendorFiles = array_diff( $phpFiles, $nonVendorFiles );

        // Fetch .json files from filtered files
        $jsonFiles = array_filter( $phpFiles, function ( $file ) {
            return strpos( $file, $this->jsonRegex ) === true;
        } );

        return [
            'lang'   => array_values( $nonVendorFiles ),
            'vendor' => array_values( $vendorFiles ),
            'json'   => array_values( $jsonFiles ),
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
    public function toFlat( $prefix = '.' )
    {
        $results = [];
        foreach ( $this->strings as $lang => $strings ) {

            if ( $lang !== 'json' ) {

                foreach ( $strings as $lang_array => $lang_messages ) {
                    $key             = $lang . $prefix . $lang_array;
                    $results[ $key ] = $lang_messages;
                }

            } else {

                foreach ( $strings as $json_lang => $json_strings ) {

                    foreach ( $json_strings as $jsLang => $json_messages ) {
                        $key             = 'json' . $prefix . $json_lang . $prefix . $jsLang;
                        $results[ $key ] = $json_messages;
                    }
                }
            }
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
        return collect( $this->strings );
    }

    /**
     * Method to parse language files.
     *
     * @param string $file
     */
    protected function parseLangFiles( $file )
    {
        // Base package name without file ending
        $packageName = basename( $file, '.php' );

        // Get package, language and file contents from language file
        // /<language_code>/(<package/)<filename>.php
        $language     = explode( DIRECTORY_SEPARATOR, $file )[ 1 ];
        $fileContents = require resource_path( 'lang' ) . DIRECTORY_SEPARATOR . $file;

        // Check if language already exists in array
        if ( array_key_exists( $language, $this->strings ) ) {
            $this->strings[ $language ][ $packageName ] = $fileContents;
        } else {
            $this->strings[ $language ] = [
                $packageName => $fileContents,
            ];
        }
    }

    /**
     * Method to parse language files from vendor folder.
     *
     * @param string $file
     */
    protected function parseVendorFiles( $file )
    {
        // Base package name without file ending
        $packageName = basename( $file, '.php' );

        // Get package, language and file contents from language file
        // /vendor/<package>/<language_code>/<filename>.php
        $package      = explode( DIRECTORY_SEPARATOR, $file )[ 2 ];
        $language     = explode( DIRECTORY_SEPARATOR, $file )[ 3 ];
        $fileContents = require resource_path( 'lang' ) . DIRECTORY_SEPARATOR . $file;

        // Check if language already exists in array
        if ( array_key_exists( $language, $this->strings ) ) {
            // Check if package already exists in language
            if ( array_key_exists( $package, $this->strings[ $language ] ) ) {
                $this->strings[ $language ][ $package ][ $packageName ] = $fileContents;
            } else {
                $this->strings[ $language ][ $package ] = [ $packageName => $fileContents ];
            }
        } else {
            $this->strings[ $language ] = [

                $package => [

                    $packageName => $fileContents,
                ],
            ];
        }
    }

    protected function parseJsonFiles( $file )
    {
        // Base package name without file ending
        $packageName = basename( $file, '.json' );

        // Get package, language and file contents from language file
        // /<language_code>/(<package/)<filename>.php
        $language     = explode( DIRECTORY_SEPARATOR, $file )[ 1 ];
        $fileContents = require resource_path( 'lang' ) . DIRECTORY_SEPARATOR . $file;

        // Check if language already exists in array
        if ( array_key_exists( 'json', $this->strings ) ) {

            if ( array_key_exists( $language, $this->strings[ 'json' ] ) ) {

                $this->strings[ 'json' ][ $language ] = $fileContents;

            }
        } else {
            $this->strings[ 'json' ] = [

                $language => $fileContents,
            ];
        }
    }
}

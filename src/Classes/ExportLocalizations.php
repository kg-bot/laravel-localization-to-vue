<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/8/18
 * Time: 6:12 PM
 */

namespace KgBot\LaravelLocalization\Classes;


use KgBot\LaravelLocalization\Events\LaravelLocalizationExported;

class ExportLocalizations
{
    public function export()
    {
        function dirToArray( $dir )
        {
            $result = [];

            $cdir = scandir( $dir );
            foreach ( $cdir as $key => $value ) {
                if ( !in_array( $value, [ ".", ".." ] ) ) {
                    if ( is_dir( $dir . DIRECTORY_SEPARATOR . $value ) ) {
                        $result[ $value ] = dirToArray( $dir . DIRECTORY_SEPARATOR . $value );
                    } else {
                        $result[] = $value;
                    }
                }
            }

            return $result;
        }

        $files = dirToArray( resource_path( 'lang' ) );

        $strings = [];

        foreach ( $files as $lang => $file ) {

            $languages = [];

            if ( $lang === 'vendor' ) {

                $package_langs = [];
                foreach ( $file as $package => $langs ) {

                    $package_lang = [];

                    foreach ( $langs as $lang => $messages ) {

                        $package_messages = [];
                        foreach ( $messages as $message ) {

                            $file_path = resource_path( 'lang/vendor/' . $package . '/' . $lang . '/' . $message );

                            $package_messages[ ( explode( '.php', basename( $file_path ) ) )[ 0 ] ] =
                                require $file_path;
                        }

                        $package_lang[ $lang ] = $package_messages;

                    }

                    $package_langs[ $package ] = $package_lang;
                }

                $strings = array_merge( $strings, $package_langs );

            } else {

                $langs = [];
                foreach ( $file as $messages ) {

                    $file_path = resource_path( 'lang/' . $lang . '/' . $messages );

                    $langs[ ( explode( '.php', basename( $file_path ) ) )[ 0 ] ] = require $file_path;
                }
                $languages[ $lang ] = $langs;
            }

            $strings = array_merge( $strings, $languages );
        }

        event( new LaravelLocalizationExported( $strings ) );

        return $strings;
    }
}
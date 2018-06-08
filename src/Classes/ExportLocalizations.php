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

                foreach ( $file as $package => $langs ) {

                    foreach ( $langs as $lang => $messages ) {
                        $package_messages = [];
                        foreach ( $messages as $message ) {

                            $file_path = resource_path( 'lang/vendor/' . $package . '/' . $lang . '/' . $message );

                            $package_messages[ ( explode( '.php', basename( $file_path ) ) )[ 0 ] ] =
                                require $file_path;
                        }

                        // Here we need for each package language to find if we already have that language in string, if
                        // we do then join package messages to it, if not create new
                        if ( in_array( $lang, array_keys( $strings ) ) ) {

                            $strings[ $lang ][ $package ] = $package_messages;

                        } else {

                            $strings[ $lang ] = [ $package => $package_messages ];
                        }
                    }
                }

            } else {

                $langs = [];
                foreach ( $file as $messages ) {

                    $file_path = resource_path( 'lang/' . $lang . '/' . $messages );

                    $langs[ ( explode( '.php', basename( $file_path ) ) )[ 0 ] ] = require $file_path;
                }

                if ( in_array( $lang, array_keys( $strings ) ) ) {

                    array_merge( $strings[ $lang ], $langs );

                } else {

                    $strings[ $lang ] = $langs;
                }
            }
        }

        event( new LaravelLocalizationExported( $strings ) );

        return $strings;
    }
}
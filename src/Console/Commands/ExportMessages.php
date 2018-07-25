<?php

namespace KgBot\LaravelLocalization\Console\Commands;

use Illuminate\Console\Command;
use KgBot\LaravelLocalization\Facades\ExportLocalizations;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class ExportMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all localization messages to JavaScript file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $messages = ExportLocalizations::export()->toArray();

        $filepath = config( 'laravel-localization.js.filepath', resource_path( 'assets/js' ) );
        $filename = config( 'laravel-localization.js.filename', 'll_messages.js' );

        $adapter    = new Local( $filepath );
        $filesystem = new Filesystem( $adapter );

        $contents = 'let messages = ' . json_encode( $messages );

        $filesystem->write( $filename, $contents );

        $this->info( 'Messages exported to JavaScript file, you can find them at ' . $filepath . DIRECTORY_SEPARATOR
                     . $filename );

        return true;
    }
}

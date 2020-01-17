<?php

namespace KgBot\LaravelLocalization\Console\Commands;

use Illuminate\Console\Command;
use KgBot\LaravelLocalization\Facades\ExportLocalizations;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class ExportMessagesToFlat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:messages-flat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all localization messages to JavaScript file flat format, suitable for Lang.js';

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
        $messages = ExportLocalizations::export()->toFlat();

        $filepath = config('laravel-localization.js.filepath', resource_path('assets/js'));
        $filename = config('laravel-localization.js.filename', 'll_messages.js');

        $adapter = new Local($filepath);
        $filesystem = new Filesystem($adapter);

        $contents = 'export default '.json_encode($messages);

        if ($filesystem->has($filename)) {
            $filesystem->delete($filename);
            $filesystem->write($filename, $contents);
        } else {
            $filesystem->write($filename, $contents);
        }

        $this->info('Messages exported to JavaScript file, you can find them at '.$filepath.DIRECTORY_SEPARATOR
                     .$filename);

        return true;
    }
}

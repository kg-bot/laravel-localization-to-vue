<?php

namespace KgBot\LaravelLocalization\Console\Commands;

use Illuminate\Console\Command;
use KgBot\LaravelLocalization\Facades\ExportLocalizations;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter as Local;

class ExportMessages extends Command
{
    /** @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed */
    protected $filepath;

    /** @var array */
    protected $messages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:messages {format=javascript}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all localization messages to JavaScript file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->filepath = config('laravel-localization.js.filepath', resource_path('assets/js'));
        $this->messages = ExportLocalizations::export()->toArray();

        $format = $this->argument('format');

        if ($format === 'javascript') {
            return $this->toJavaScript();
        }
        if ($format === 'json') {
            return $this->toJson();
        }

        $this->error("Format {$format} is not currently supported, you can use callback function if you need additional modification of exported array.");

        return 1;
    }

    protected function toJavaScript()
    {
        $filename = config('laravel-localization.js.filename', 'll_messages.js');

        $adapter = new Local($this->filepath);
        $filesystem = new Filesystem($adapter);

        $contents = 'export default '.json_encode($this->messages);

        if ($filesystem->has($filename)) {
            $filesystem->delete($filename);
            $filesystem->write($filename, $contents);
        } else {
            $filesystem->write($filename, $contents);
        }

        $this->info('Messages exported to JavaScript file, you can find them at '.$this->filepath.DIRECTORY_SEPARATOR
                    .$filename);

        return 0;
    }

    protected function toJson()
    {
        foreach ($this->messages as $language_key => $translations) {
            foreach ($translations as $translation_key => $translate) {
                $filepath = "$this->filepath/$language_key";
                $filename = "$translation_key.json";

                $adapter = new Local($filepath);
                $filesystem = new Filesystem($adapter);

                $contents = json_encode($translate, JSON_PRETTY_PRINT);

                if ($filesystem->has($filename)) {
                    $filesystem->delete($filename);
                    $filesystem->write($filename, $contents);
                } else {
                    $filesystem->write($filename, $contents);
                }
            }
        }

        $this->info('Messages exported to JSON files, you can find them at '.$this->filepath);

        return 0;
    }
}

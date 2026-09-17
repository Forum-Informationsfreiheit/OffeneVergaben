<?php

namespace Tests\Feature\Jobs;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Monolog\Handler\NullHandler;
use Symfony\Component\VarDumper\VarDumper;
use Tests\Feature\DatabaseTestCase;

/**
 * Base class for the tests of the jobs in app/Jobs and of the fif:* commands that run them.
 */
abstract class JobTestCase extends DatabaseTestCase
{
    /**
     * @var string|null see useTemporaryPaths()
     */
    private $temporaryPath;

    protected function setUp(): void
    {
        parent::setUp();

        // the jobs report their progress with dump() for the console: keep the test output clean
        VarDumper::setHandler(function () {
        });

        // and they log to storage/logs, which would mix test records into the logs of the dev environment
        config([
            'logging.default'                  => 'test_null',
            'logging.channels.test_null'       => ['driver' => 'monolog', 'handler' => NullHandler::class],
            'logging.channels.processor_daily' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        ]);
    }

    protected function tearDown(): void
    {
        VarDumper::setHandler(null);

        if ($this->temporaryPath) {
            File::deleteDirectory($this->temporaryPath);
        }

        parent::tearDown();
    }

    /**
     * Points storage_path() and public_path() to an empty temporary directory (with storage/app and public/), so
     * the jobs don't write into storage/app, public/tmp or public/sitemap.xml of the project.
     */
    protected function useTemporaryPaths()
    {
        $this->temporaryPath = sys_get_temp_dir().'/offenevergaben-test-'.Str::random(8);

        mkdir($this->temporaryPath.'/storage/app', 0777, true);
        mkdir($this->temporaryPath.'/public', 0777, true);

        $this->app->useStoragePath($this->temporaryPath.'/storage');
        $this->app->instance('path.public', $this->temporaryPath.'/public');
    }
}

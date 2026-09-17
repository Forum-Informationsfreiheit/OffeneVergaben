<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CleanUpStaleTemporaryDownloadLinksJob;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CleanUpStaleTemporaryDownloadLinksJobTest extends JobTestCase
{
    private $dump;

    protected function setUp(): void
    {
        parent::setUp();

        // the job deletes entries in public/tmp: use a temporary directory
        $this->useTemporaryPaths();
        mkdir(public_path('tmp'));

        // the dump the download links point to
        $this->dump = storage_path('app/kerndaten_csv_dump_20260101_0400.zip');
        file_put_contents($this->dump, 'zip');
    }

    public function testDeletesLinksOlderThan90Minutes()
    {
        $link = $this->createLink();

        Carbon::setTestNow(Carbon::now()->addMinutes(91));
        (new CleanUpStaleTemporaryDownloadLinksJob())->handle();

        $this->assertFalse(is_link($link));
        // only the link is deleted, not the dump
        $this->assertFileExists($this->dump);
    }

    public function testKeepsLinksYoungerThan90Minutes()
    {
        $link = $this->createLink();

        Carbon::setTestNow(Carbon::now()->addMinutes(89));
        (new CleanUpStaleTemporaryDownloadLinksJob())->handle();

        $this->assertTrue(is_link($link));
    }

    /**
     * The age of the link counts, not the age of the (usually older) dump it points to.
     */
    public function testUsesAgeOfLinkNotOfDump()
    {
        touch($this->dump, time() - 24 * 60 * 60);
        $link = $this->createLink();

        (new CleanUpStaleTemporaryDownloadLinksJob())->handle();

        $this->assertTrue(is_link($link));
    }

    /**
     * A link as DownloadController@downloadStaticFile creates it.
     */
    private function createLink()
    {
        $link = public_path('tmp/kerndaten_dailydump_202601010400_'.Str::lower(Str::random(8)).'.zip');
        symlink($this->dump, $link);

        return $link;
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\MakeDatasetsCsvDumpJob;

class DownloadControllerTest extends PublicTestCase
{
    const DUMP_TIMESTAMP = '2026-09-01 04:00:00';

    // file name prefix of the temporary download links for DUMP_TIMESTAMP
    const LINK_PREFIX = 'kerndaten_dailydump_202609010400_';

    private $dumpFile;

    protected function setUp(): void
    {
        parent::setUp();

        // the current dump is looked up in the cache: use an empty array cache, never the one of the dev environment
        config(['cache.default' => 'array']);

        $this->dumpFile = tempnam(sys_get_temp_dir(), 'kerndaten_dump');
    }

    protected function tearDown(): void
    {
        // symlinks in public/tmp created by the download tests
        foreach (glob(public_path('tmp/'.self::LINK_PREFIX.'*.zip')) ?: [] as $link) {
            unlink($link);
        }
        if ($this->dumpFile) {
            unlink($this->dumpFile);
        }

        parent::tearDown();
    }

    public function testIndexShowsCurrentDump()
    {
        $this->cacheCurrentDump();

        $response = $this->get(route('public::downloads'));

        $response->assertStatus(200);
        $response->assertViewIs('public.downloads.index');
        $response->assertSee('01.09.2026');
        $response->assertSee(route('public::download-static-file', ['fileName' => 'kerndaten_dump_daily', 'format' => 'csv']));
    }

    /**
     * TODO: bug — without the dump cache keys (fresh install, cleared cache, before the first fif:dump-datasets)
     * DownloadController@index passes null to filesize(), which fails with HTTP 500. Render the page without the
     * file instead, then remove markTestIncomplete() here.
     */
    public function testIndexWithoutDumpLoads()
    {
        $this->markTestIncomplete('DownloadController@index calls filesize(null) when no dump is cached (HTTP 500).');

        $response = $this->get(route('public::downloads'));

        $response->assertStatus(200);
    }

    public function testDownloadLinksCurrentDumpTemporarily()
    {
        $this->cacheCurrentDump();

        $response = $this->get(route('public::download-static-file', ['fileName' => 'kerndaten_dump_daily', 'format' => 'csv']));

        $response->assertStatus(200);
        $response->assertViewIs('public.downloads.show');

        $link = $response->viewData('link');
        $this->assertStringStartsWith(url('tmp/'.self::LINK_PREFIX), $link);
        $this->assertSame($this->dumpFile, readlink(public_path('tmp/'.basename($link))));
    }

    /**
     * Only the daily dump can be downloaded.
     */
    public function testUnknownFileRedirectsToDownloads()
    {
        $this->cacheCurrentDump();

        $response = $this->get(route('public::download-static-file', ['fileName' => 'passwords', 'format' => 'csv']));

        $response->assertRedirect(route('public::downloads'));
        $this->assertEmpty(glob(public_path('tmp/'.self::LINK_PREFIX.'*.zip')));
    }

    /**
     * @dataProvider invalidFormatProvider
     */
    public function testMissingOrUnsupportedFormatRedirectsWithError(array $format)
    {
        $this->cacheCurrentDump();

        $response = $this->get(route('public::download-static-file', ['fileName' => 'kerndaten_dump_daily'] + $format));

        $response->assertRedirect(route('public::downloads'));
        $response->assertSessionHas('flash_notification', function ($messages) {
            return $messages->first()->level === 'danger';
        });
        $this->assertEmpty(glob(public_path('tmp/'.self::LINK_PREFIX.'*.zip')));
    }

    public function invalidFormatProvider()
    {
        return [
            'format missing'     => [[]],
            'format unsupported' => [['format' => 'xlsx']],
        ];
    }

    /**
     * Stores the test dump as current dump, like fif:dump-datasets does.
     */
    private function cacheCurrentDump()
    {
        cache()->forever(MakeDatasetsCsvDumpJob::CACHE_CURRENT_PATH_ABSOLUTE, $this->dumpFile);
        cache()->forever(MakeDatasetsCsvDumpJob::CACHE_CURRENT_FILE_TIMESTAMP, self::DUMP_TIMESTAMP);
    }
}

<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateSitemapJob;
use App\Page;
use App\Post;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * The dataset and organization sitemaps read every row of these tables. In these tests the job gets the rows in
 * memory instead (see job()), the queries themselves are not tested.
 */
class GenerateSitemapJobTest extends JobTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // the job works in storage/app/sitemap and replaces public/sitemap.xml and public/sitemaps/
        $this->useTemporaryPaths();
    }

    public function testPagesSitemapListsPublishedPages()
    {
        $published = $this->createPage(['published_at' => now()]);
        $draft     = $this->createPage();
        // the dev database may already have this page: change it instead of adding a second one
        $impressum = Page::where('slug', 'impressum')->first() ?: new Page();
        $impressum->forceFill(['title' => 'Impressum', 'slug' => 'impressum', 'body' => 'Inhalt', 'published_at' => now()])->save();

        $xml = $this->job()->sitemap('createPagesSitemap', 'pages');

        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/page/'.$published->slug).'</loc>', $xml);
        $this->assertStringNotContainsString(url('/page/'.$draft->slug), $xml);
        // reserved pages are also listed under their own url
        $this->assertStringContainsString('<loc>'.url('/impressum').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/downloads').'</loc>', $xml);
    }

    /**
     * TODO: bug (DOCUMENTATION.md, known quirk 2) — GenerateSitemapJob::createPagesSitemap() lists posts as
     * /posts/{slug}, but the route is /neuigkeiten/{slug}, so search engines get a 404 for every post. Use
     * route('public::show-post', $post->slug), then remove markTestIncomplete() here.
     */
    public function testPagesSitemapLinksPostsToNeuigkeiten()
    {
        $this->markTestIncomplete('GenerateSitemapJob lists posts under /posts/ instead of /neuigkeiten/.');

        $post = Post::forceCreate([
            'title'        => 'Beitrag',
            'slug'         => 'beitrag-'.Str::lower(Str::random(8)),
            'summary'      => 'Kurzfassung',
            'body'         => 'Inhalt',
            'published_at' => now(),
        ]);

        $xml = $this->job()->sitemap('createPagesSitemap', 'pages');

        $this->assertStringContainsString('<loc>'.url('/neuigkeiten/'.$post->slug).'</loc>', $xml);
    }

    /**
     * CPV codes of the top three levels (trimmed code up to 4 digits), with lower priority for lower levels.
     */
    public function testCpvsSitemapListsTopThreeLevels()
    {
        $this->createCpv('99000000');
        $this->createCpv('99100000');
        $this->createCpv('99110000');
        $this->createCpv('99111000');

        $xml = $this->job()->sitemap('createCpvsSitemap', 'cpvs');

        $this->assertUrlWithPriority($xml, '/branchen?node=99000000', '0.7');
        $this->assertUrlWithPriority($xml, '/branchen?node=99100000', '0.5');
        $this->assertUrlWithPriority($xml, '/branchen?node=99110000', '0.4');
        $this->assertStringNotContainsString(url('/branchen?node=99111000'), $xml);
    }

    /**
     * Dataset links are written into files of 5000 links (MAX_LINKS_PER_FILE), older versions with lower priority.
     */
    public function testDatasetsSitemapsAreSplitIntoFilesOf5000Links()
    {
        $datasets = [];
        for ($id = 1; $id <= 5001; $id++) {
            $datasets[] = (object) ['id' => $id, 'is_current_version' => $id !== 2, 'updated_at' => Carbon::parse('2026-09-01 12:00:00')];
        }
        $job = $this->job($datasets);

        $first  = $job->sitemap('createDatasetsSitemaps', 'kerndaten', 1);
        $second = $job->sitemapFile('kerndaten', 2);

        $this->assertSame(5000, substr_count($first, '<url>'));
        $this->assertUrlWithPriority($first, '/aufträge/1', '0.5');
        $this->assertUrlWithPriority($first, '/aufträge/2', '0.3');
        $this->assertStringContainsString('<lastmod>2026-09-01T12:00:00', $first);
        $this->assertSame(1, substr_count($second, '<url>'));
        $this->assertStringContainsString('<loc>'.url('/aufträge/5001').'</loc>', $second);
    }

    public function testOrganizationsSitemapsLinkToContractorsAndOfferors()
    {
        $updatedAt = Carbon::parse('2026-09-01 12:00:00');
        $job       = $this->job(
            [],
            [(object) ['organization_id' => 11, 'updated_at' => $updatedAt]],
            [(object) ['organization_id' => 22, 'updated_at' => $updatedAt]]
        );

        $contractors = $job->sitemap('createContractorsSitemaps', 'lieferanten', 1);
        $offerors    = $job->sitemap('createOfferorsSitemaps', 'auftraggeber', 1);

        $this->assertUrlWithPriority($contractors, '/lieferanten/11', '0.5');
        $this->assertUrlWithPriority($offerors, '/auftraggeber/22', '0.5');
    }

    /**
     * handle() writes all sitemaps, lists them in public/sitemap.xml and replaces the old files.
     */
    public function testHandlePublishesAllSitemaps()
    {
        $updatedAt = Carbon::parse('2026-09-01 12:00:00');
        file_put_contents(public_path('sitemap.xml'), 'alt');
        mkdir(public_path('sitemaps'));
        file_put_contents(public_path('sitemaps/sitemap-veraltet.xml'), 'alt');

        $this->job(
            [(object) ['id' => 1, 'is_current_version' => true, 'updated_at' => $updatedAt]],
            [(object) ['organization_id' => 11, 'updated_at' => $updatedAt]],
            [(object) ['organization_id' => 22, 'updated_at' => $updatedAt]]
        )->handle();

        $index = file_get_contents(public_path('sitemap.xml'));
        $files = [
            'sitemap-auftraggeber001.xml',
            'sitemap-cpvs.xml',
            'sitemap-kerndaten001.xml',
            'sitemap-lieferanten001.xml',
            'sitemap-pages.xml',
        ];
        foreach ($files as $file) {
            $this->assertStringContainsString('<loc>'.url('/sitemaps/'.$file).'</loc>', $index);
        }
        $this->assertSame($files, array_values(array_diff(scandir(public_path('sitemaps')), ['.', '..'])));
        // the working directory is removed
        $this->assertSame(['.', '..'], scandir(storage_path('app/sitemap')));
    }

    /**
     * @param array $datasets rows of datasetsStream()
     * @param array $contractors rows of contractorsStream()
     * @param array $offerors rows of offerorsStream()
     */
    private function job(array $datasets = [], array $contractors = [], array $offerors = [])
    {
        return new class($datasets, $contractors, $offerors) extends GenerateSitemapJob {
            private $rows;

            public function __construct(array $datasets, array $contractors, array $offerors)
            {
                parent::__construct();

                $this->rows = ['datasets' => $datasets, 'contractors' => $contractors, 'offerors' => $offerors];
            }

            /**
             * Runs a single step of handle() and returns the sitemap file it wrote.
             */
            public function sitemap($step, $name, $index = null)
            {
                // the working directory is named after the current millisecond: create it only once per job
                if (!$this->dtDir) {
                    $this->prepareWorkingDirectory();
                }
                $this->{$step}();

                return $this->sitemapFile($name, $index);
            }

            public function sitemapFile($name, $index = null)
            {
                return file_get_contents($this->getSitemapPath($name, $index));
            }

            protected function datasetsStream($offset = 0, $blockSize = 500)
            {
                return $this->stream('datasets', $offset, $blockSize);
            }

            protected function contractorsStream($offset = 0, $blockSize = 500)
            {
                return $this->stream('contractors', $offset, $blockSize);
            }

            protected function offerorsStream($offset = 0, $blockSize = 500)
            {
                return $this->stream('offerors', $offset, $blockSize);
            }

            private function stream($name, $offset, $blockSize)
            {
                $rows = array_slice($this->rows[$name], $offset, $blockSize);

                return $rows ? collect($rows) : null;
            }
        };
    }

    private function assertUrlWithPriority($xml, $path, $priority)
    {
        $this->assertRegExp(
            '#<loc>'.preg_quote(url($path), '#').'</loc>\s*(<lastmod>[^<]*</lastmod>\s*)?(<changefreq>[^<]*</changefreq>\s*)?'
                .'<priority>'.preg_quote($priority, '#').'</priority>#',
            $xml
        );
    }

    private function createPage(array $attributes = [])
    {
        return Page::forceCreate(array_merge([
            'title' => 'Seite '.Str::random(8),
            'slug'  => 'seite-'.Str::lower(Str::random(8)),
            'body'  => 'Inhalt',
        ], $attributes));
    }
}

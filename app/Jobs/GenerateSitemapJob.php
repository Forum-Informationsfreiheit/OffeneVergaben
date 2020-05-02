<?php

namespace App\Jobs;

use App\Contractor;
use App\CPV;
use App\Dataset;
use App\Offeror;
use App\Organization;
use App\Page;
use App\Post;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemapJob
{
    use Dispatchable;

    /**
     * This is a storage path, use in combination with
     * storage_path(<const>) to get the absolute path.
     *
     * The relative path from the root directory can be build with
     * ./storage/app/<const>
     */
    const STORAGE_OUTPUT_DIRECTORY = "sitemap";

    /**
     * @see https://www.sitemaps.org/protocol.html#index
     * The official maximum number of allowed links per file is 50000
     *
     * Even though max. is 50000 we use only 1/10th of that to
     * keep file size down and request speed fast
     */
    const MAX_LINKS_PER_FILE = 5000;

    protected $rootDir;
    protected $dtDir;
    protected $sitemapsDir;

    /**
     * Prepare sitemap xml files in STORAGE_OUTPUT_DIRECTORY.
     * At the end move the files to public/
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // generate sitemaps in timestamped directory in storage/app/sitemap
        $this->prepareWorkingDirectory();

        $this->createPagesSitemap();
        $this->createCpvsSitemap();
        $this->createDatasetsSitemaps();
        $this->createContractorsSitemaps();
        $this->createOfferorsSitemaps();

        $this->createIndex();

        // after every task is complete, move the files to the public directory
        $this->finish();
    }

    protected function prepareWorkingDirectory() {
        $this->rootDir = storage_path('app' . DIRECTORY_SEPARATOR .self::STORAGE_OUTPUT_DIRECTORY);
        if (!file_exists($this->rootDir)) {
            mkdir($this->rootDir);
        }

        // create the current working directory, timestamped to milliseconds
        // this directory holds the sitemap index file and the sub directory for the sitemaps
        $this->dtDir = $this->rootDir . DIRECTORY_SEPARATOR . Carbon::now()->format('Ymd_Hisv');
        mkdir($this->dtDir);

        // create the sub directory that holds each sitemap.xml file
        $this->sitemapsDir = $this->dtDir . DIRECTORY_SEPARATOR . 'sitemaps';
        mkdir($this->sitemapsDir);
    }

    /**
     * Manually add some pages, with custom priority etc.
     *
     * NOTE: Modification date and priority can be set manually for each item or if
     *       modification date is not set manually <now> will be used instead.
     */
    protected function createPagesSitemap() {
        $sitemap = Sitemap::create();
        $sitemapPath = $this->getSitemapPath('pages');

        // add root
        $sitemap->add(Url::create('/')->setPriority(1));

        // add pages
        $pages = Page::published()->get();
        foreach($pages as $page) {
            // 'reserved' page ?
            if ($page->slug === 'überuns') {
                $sitemap->add(Url::create('/überuns')->setPriority(0.6)->setLastModificationDate($page->updated_at)->setChangeFrequency('weekly'));
            }
            if ($page->slug === 'impressum') {
                $sitemap->add(Url::create('/impressum')->setPriority(0.3)->setLastModificationDate($page->updated_at)->setChangeFrequency('monthly'));
            }
            if ($page->slug === 'datenschutz') {
                $sitemap->add(Url::create('/datenschutz')->setPriority(0.3)->setLastModificationDate($page->updated_at)->setChangeFrequency('monthly'));
            }

            // other pages
            $sitemap->add(Url::create('/page/'.$page->slug)->setPriority(0.5)->setLastModificationDate($page->updated_at)->setChangeFrequency('weekly'));
        }

        // add posts
        $posts = Post::published()->get();
        foreach($posts as $post) {
            $sitemap->add(Url::create('/posts/'.$post->slug)->setPriority($post->featured ? 0.8 : 0.5)->setChangeFrequency('weekly'));
        }

        // other
        $sitemap->add(Url::create('/downloads')->setPriority(0.8)->setChangeFrequency('daily'));

        $sitemap->writeToFile($sitemapPath);
    }

    protected function createCpvsSitemap() {
        $sitemap = Sitemap::create();
        $sitemapPath = $this->getSitemapPath('cpvs');

        // Use the top 3 levels, skip anything lower than that
        $cpvs = CPV::where(DB::raw('LENGTH(trimmed_code)'),'<=',4)->get();

        foreach($cpvs as $cpv) {
            // give the top level a higher priority
            $priority = 0.4;
            if($cpv->level === 2) { $priority = 0.7; } // level 2 is actually top level
            if($cpv->level === 3) { $priority = 0.5; }

            $sitemap->add(Url::create('/branchen?node='.$cpv->code)->setPriority($priority));
        }

        $sitemap->writeToFile($sitemapPath);
    }

    protected function createDatasetsSitemaps() {
        $name = "kerndaten";
        // Datasets and organization sitemaps need to be handled in a blocked fashion to
        // avoid memory issues on reading,
        // and keep the maximum links per file below the 'official' limit of 50000
        $blockSizeRead = 500;

        // after 5000 items start the next file
        $blockSizeWrite = self::MAX_LINKS_PER_FILE;
        $datasetIndex = 0;
        $sitemapIndex = 1;
        $sitemap = Sitemap::create();

        while($rows = $this->datasetsStream($datasetIndex, $blockSizeRead)) {

            foreach($rows as $row) {
                $sitemap->add(Url::create('/aufträge/'.$row->id)
                    ->setPriority($row->is_current_version ? 0.5 : 0.3) // use lower priority for historic datasets
                    ->setChangeFrequency('monthly')
                    ->setLastModificationDate($row->updated_at)
                );

                $datasetIndex++;

                // write to file each time we pass the magic number of $blockSizeWrite
                if ($datasetIndex % $blockSizeWrite === 0) {
                    $sitemap->writeToFile($this->getSitemapPath($name,$sitemapIndex));

                    // new the next one up
                    $sitemap = Sitemap::create();
                    $sitemapIndex++;
                }
            }
            // read the next bunch
        }

        // as reads happen more frequent than writes it is very likely that after the last
        // iteration of the while loop happened the last $blockSize-1 links added to
        // the sitemap have never actually been written to file.
        // make sure to leave no record behind:
        $sitemap->writeToFile($this->getSitemapPath($name,$sitemapIndex));
    }

    protected function datasetsStream($offset = 0, $blockSize = 500) {
        $query = Dataset::select(['id','is_current_version','updated_at']);
        $query->offset($offset)->limit($blockSize);

        $records = $query->get();

        return $records->count() > 0 ? $records : null;
    }

    /**
     *
     */
    protected function createContractorsSitemaps() {
        $name = "lieferanten";
        $blockSizeRead = 500;

        $blockSizeWrite = self::MAX_LINKS_PER_FILE;
        $contractorIndex = 0;
        $sitemapIndex = 1;
        $sitemap = Sitemap::create();

        while($rows = $this->contractorsStream($contractorIndex, $blockSizeRead)) {

            foreach($rows as $row) {
                $sitemap->add(Url::create('/lieferanten/'.$row->organization_id)
                    ->setPriority(0.5)
                    ->setChangeFrequency('daily')
                    ->setLastModificationDate($row->updated_at)
                );

                $contractorIndex++;

                if ($contractorIndex % $blockSizeWrite === 0) {
                    $sitemap->writeToFile($this->getSitemapPath($name,$sitemapIndex));

                    $sitemap = Sitemap::create();
                    $sitemapIndex++;
                }
            }
        }

        $sitemap->writeToFile($this->getSitemapPath($name,$sitemapIndex));
    }

    protected function contractorsStream($offset = 0, $blockSize = 500) {
        $query = Contractor::select([
            'contractors.organization_id',
            'organizations.updated_at'
        ]);
        $query->join('datasets','contractors.dataset_id','=','datasets.id');
        $query->join('organizations','contractors.organization_id','=','organizations.id');
        $query->where('datasets.disabled_at',null);
        $query->groupBy('contractors.organization_id','organizations.updated_at');
        $query->offset($offset)->limit($blockSize);

        $records = $query->get();

        return $records->count() > 0 ? $records : null;
    }

    /**
     *
     */
    protected function createOfferorsSitemaps() {
        $name = "auftraggeber";
        $blockSizeRead = 500;

        $blockSizeWrite = self::MAX_LINKS_PER_FILE;
        $offerorIndex = 0;
        $sitemapIndex = 1;
        $sitemap = Sitemap::create();

        while($rows = $this->offerorsStream($offerorIndex, $blockSizeRead)) {

            foreach($rows as $row) {
                $sitemap->add(Url::create('/auftraggeber/'.$row->organization_id)
                    ->setPriority(0.5)
                    ->setChangeFrequency('daily')
                    ->setLastModificationDate($row->updated_at)
                );

                $offerorIndex++;

                if ($offerorIndex % $blockSizeWrite === 0) {
                    $sitemap->writeToFile($this->getSitemapPath($name,$sitemapIndex));

                    $sitemap = Sitemap::create();
                    $sitemapIndex++;
                }
            }
        }

        $sitemap->writeToFile($this->getSitemapPath($name,$sitemapIndex));
    }

    protected function offerorsStream($offset = 0, $blockSize = 500) {
        $query = Offeror::select([
            'offerors.organization_id',
            'organizations.updated_at'
        ]);
        $query->join('datasets','offerors.dataset_id','=','datasets.id');
        $query->join('organizations','offerors.organization_id','=','organizations.id');
        $query->where('datasets.disabled_at',null);
        $query->groupBy('offerors.organization_id','organizations.updated_at');
        $query->offset($offset)->limit($blockSize);

        $records = $query->get();

        return $records->count() > 0 ? $records : null;
    }

    protected function getNumericIndexWithLeadingZeros($number, $length = 3) {
        return str_pad((string)$number, $length, "0", STR_PAD_LEFT);
    }

    protected function getSitemapPath($name, $index = null) {
        return $this->sitemapsDir . DIRECTORY_SEPARATOR .
            'sitemap-' . $name .
            ($index ? $this->getNumericIndexWithLeadingZeros($index) : '') .
            '.xml';
    }

    /**
     * Create the index file
     */
    protected function createIndex() {
        $sitemapIndex = SitemapIndex::create();

        $files = scandir($this->sitemapsDir);

        foreach($files as $sitemap) {
            if ($sitemap === '.' || $sitemap === '..') {
                continue;
            }
            $sitemapIndex->add('/sitemaps/'.basename($sitemap));
        }

        $sitemapIndex->writeToFile($this->dtDir . DIRECTORY_SEPARATOR .
            'sitemap.xml');
    }

    /**
     * Move all files from the working directory to public/
     */
    protected function finish() {
        // move the index file
        $oldSitemapPath = $this->dtDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
        $newSitemapPath = public_path('sitemap.xml');
        if (file_exists($newSitemapPath)) {
            unlink($newSitemapPath);    // make sure to delete the stale sitemap.xml
        }
        rename($oldSitemapPath, $newSitemapPath);

        // move the sitemaps directory containing the actual sitemaps
        $oldSitemapsDir = $this->sitemapsDir;
        $newSitemapsDir = public_path('sitemaps');
        if (file_exists($newSitemapsDir)) { // make sure to delete the stale sitemaps dir
            $files = scandir($newSitemapsDir);
            foreach($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                unlink(public_path('sitemaps'.DIRECTORY_SEPARATOR.$file));
            }
            rmdir($newSitemapsDir);
        }
        rename($oldSitemapsDir,$newSitemapsDir);

        // finally remove the temporary working directory
        rmdir($this->dtDir);
    }
}

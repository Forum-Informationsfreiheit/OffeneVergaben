<?php

namespace App\Jobs;

use App\Dataset;
use App\Origin;
use App\Scraper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Scrape
{
    use Dispatchable;

    protected $scraper;

    protected $timestamp;

    /**
     * @param Scraper $scraper
     */
    public function __construct(Scraper $scraper)
    {
        $this->scraper = $scraper;
        $this->scraper->useDbLog(true);

        // use same timestamp for all requests
        $this->timestamp = Carbon::now();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get only active origins
        $origins = Origin::active()->get();

        foreach($origins as $origin) {
            $this->handleOrigin($origin);
        }
    }

    protected function handleOrigin($origin) {

        // step 1: scrape origin
        dump('Checking origin: ' . $origin->name . ' (' . $origin->id . ')');
        $datasets = $this->scraper->scrapeOrigin($origin->url);

        if ($datasets == null) {
            // many possible errors here
            // most likely scraper could not reach the endpoint
            // todo error handling ???

            return;
        }

        // keep current run timestamp on origin
        $origin->last_scraped_at = $this->timestamp;
        $origin->save();

        // check for new / updated datasets, and return list of datasets
        // that need to be processed further
        $scrapeDetails = $this->manageDatasets($origin, $datasets);

        dump(count($scrapeDetails) . ' datasets need to be updated. ');

        $idx = 0;

        // step 2: scrape details
        foreach($scrapeDetails as $dataset) {
            $idx++;
            dump('('.$origin->id.') Scraping dataset: '.$dataset->reference_id);

            $version = $this->scraper->scrapeDataset($origin->reference_id, $dataset->reference_id, $dataset->url);

            $this->manageUpdateDataset($dataset, $version);

            // TODO this is just temporary
            /*
            if ($idx >= 2) {
                break;
            }
            */
        }
    }

    /**
     * @param $origin
     * @param $datasets
     * @return array
     */
    protected function manageDatasets($origin, $datasets) {

        $scrapeDetails = [];

        foreach($datasets as $dataset) {
            $existing = Dataset::where('origin_id',$origin->id)
                ->where('reference_id',$dataset['@attributes']['id'])->first();

            // new dataset! create database record
            if (!$existing) {
                // add dataset for further processing (scrape details)
                $scrapeDetails[] = $this->manageNewDataset($origin, $dataset);
            } else {
                $existing = $this->manageExistingDataset($origin, $dataset, $existing);

                if ($existing) {
                    $scrapeDetails[] = $existing;
                }
            }
        }

        return $scrapeDetails;
    }

    protected function manageNewDataset($origin, $dataset) {

        $obj = new Dataset();
        $obj->reference_id = $dataset['@attributes']['id'];
        $obj->origin_id = $origin->id;
        $obj->url = $dataset['url'];
        $obj->last_modified_at = Carbon::createFromTimeString($dataset['@attributes']['lastmod']);

        // init version value with 0
        $obj->version = 0;
        $obj->version_scraped = 0;

        // temp deleteme todo
        $obj->last_scraped_at = Carbon::now()->subWeek()->subWeek();

        $obj->save();

        return $obj;
    }

    /**
     * @param $origin
     * @param $dataset
     * @param $existing
     * @return bool | true if dataset should be scraped
     */
    protected function manageExistingDataset($origin, $dataset, $existing) {
        $lastMod = Carbon::createFromTimeString($dataset['@attributes']['lastmod']);

        // first step, touch record and update lastmod column
        if ($lastMod->greaterThan($existing->last_modified_at)) {
            $existing->last_modified_at = $lastMod;
            $existing->save();
        }

        // if for some reason (probably api endpoint down), dataset has never been scraped
        // --> do it now
        if ($existing->last_scraped_at == null) {
            return $existing;
        }

        // has been scraped at least once, compare with last mod timestamp
        if ($lastMod->greaterThan($existing->last_scraped_at)) {
            return $existing;
        }

        return null;
    }

    /**
     * @param $record
     * @param $version
     */
    protected function manageUpdateDataset($record, $version) {
        $record->version_scraped = $version;
        $record->last_scraped_at = $this->timestamp;
        $record->save();

        return $record;
    }
}

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

    /**
     * @param Scraper $scraper
     */
    public function __construct(Scraper $scraper)
    {
        $this->scraper = $scraper;
        $this->scraper->useDbLog(true);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $origins = Origin::all();

        foreach($origins as $origin) {
            $this->handleOrigin($origin);
        }
    }

    protected function handleOrigin($origin) {

        // step 1: scrape origin
        $datasets = $this->scraper->scrapeOrigin($origin->url);

        if ($datasets == null) {
            // many possible errors here
            // most likely scraper could not reach the endpoint
            // todo error handling ???

            return;
        }

        // keep current run timestamp on origin
        $origin->last_scraped_at = Carbon::now();
        $origin->save();

        // check for new / updated datasets, and return list of datasets
        // that need to be processed further
        $scrapeDetails = $this->manageDatasets($origin, $datasets);

        dd($scrapeDetails);

        // step 2: scrape details
        foreach($scrapeDetails as $dataset) {

            $result = $this->scraper->scrapeDataset($origin, $dataset);

            dd($result);
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
                ->where('guid',$dataset['@attributes']['id'])->first();

            // new dataset! create database record
            if (!$existing) {
                $this->manageNewDataset($origin, $dataset);

                // add dataset for further processing (scrape details)
                $scrapeDetails[] = $dataset;
            } else {
                $scrapeIt = $this->manageExistingDataset($origin, $dataset, $existing);

                if ($scrapeIt) {
                    $scrapeDetails[] = $dataset;
                }
            }
        }

        return $scrapeDetails;
    }

    protected function manageNewDataset($origin, $dataset) {

        $obj = new Dataset();
        $obj->guid = $dataset['@attributes']['id'];
        $obj->origin_id = $origin->id;
        $obj->url = $dataset['url'];
        $obj->last_modified_at = Carbon::createFromTimeString($dataset['@attributes']['lastmod']);

        // init version value with 0
        $obj->version = 0;
        $obj->version_scraped = 0;

        // temp deleteme todo
        $obj->last_scraped_at = Carbon::now()->subWeek()->subWeek();

        $obj->save();
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
            return true;
        }

        // has been scraped at least once, compare with last mod timestamp
        if ($lastMod->greaterThan($existing->last_scraped_at)) {
            return true;
        }

        return false;
    }
}

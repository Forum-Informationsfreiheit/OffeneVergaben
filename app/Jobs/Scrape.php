<?php

namespace App\Jobs;

use App\Datasource;
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
        $datasources = $this->scraper->scrapeOrigin($origin->url);

        if ($datasources == null) {
            // many possible errors here
            // most likely scraper could not reach the endpoint
            // todo error handling ???

            return;
        }

        // keep current run timestamp on origin
        $origin->last_scraped_at = $this->timestamp;
        $origin->save();

        // check for new / updated datasources, and return list of datasources
        // that need to be processed further
        $scrapeDetails = $this->manageDatasources($origin, $datasources);

        dump(count($scrapeDetails) . ' datasources need to be updated. ');

        $idx = 0;

        // step 2: scrape details
        foreach($scrapeDetails as $datasource) {
            $idx++;
            dump('('.$origin->id.') Scraping datasource: '.$datasource->reference_id);

            $version = $this->scraper->scrapeDatasource($origin->reference_id, $datasource->reference_id, $datasource->url);

            $this->manageUpdateDatasource($datasource, $version);

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
     * @param $datasources
     * @return array
     */
    protected function manageDatasources($origin, $datasources) {

        $scrapeDetails = [];

        foreach($datasources as $datasource) {
            $existing = Datasource::where('origin_id',$origin->id)
                ->where('reference_id',$datasource['@attributes']['id'])->first();

            // new datasource! create database record
            if (!$existing) {
                // add datasource for further processing (scrape details)
                $scrapeDetails[] = $this->manageNewDatasource($origin, $datasource);
            } else {
                $existing = $this->manageExistingDatasource($origin, $datasource, $existing);

                if ($existing) {
                    $scrapeDetails[] = $existing;
                }
            }
        }

        return $scrapeDetails;
    }

    protected function manageNewDatasource($origin, $datasource) {

        $obj = new Datasource();
        $obj->reference_id = $datasource['@attributes']['id'];
        $obj->origin_id = $origin->id;
        $obj->url = $datasource['url'];
        $obj->last_modified_at = Carbon::createFromTimeString($datasource['@attributes']['lastmod']);

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
     * @param $datasource
     * @param $existing
     * @return bool | true if datasource should be scraped
     */
    protected function manageExistingDatasource($origin, $datasource, $existing) {
        $lastMod = Carbon::createFromTimeString($datasource['@attributes']['lastmod']);

        // first step, touch record and update lastmod column
        if ($lastMod->greaterThan($existing->last_modified_at)) {
            $existing->last_modified_at = $lastMod;
            $existing->save();
        }

        // if for some reason (probably api endpoint down), datasource has never been scraped
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
    protected function manageUpdateDatasource($record, $version) {
        $record->version_scraped = $version;
        $record->last_scraped_at = $this->timestamp;
        $record->save();

        return $record;
    }
}

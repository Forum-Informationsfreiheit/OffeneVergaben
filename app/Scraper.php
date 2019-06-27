<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Scraper
{
    protected $client;
    protected $useDbLog = false;
    protected $interval = 2;
    protected $last_request_at = null;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function useDbLog($use) {
        $this->useDbLog = $use;
    }

    /**
     * Set the interval in seconds for requests.
     *
     * E.g. an interval of 2 means that requests are send once every 2 seconds
     *
     * @param $interval
     */
    public function setInterval($interval) {
        $this->interval = $interval;
    }

    /**
     * Scrape a given origin url (Kerndaten-Quelle)
     *
     * @param $url
     * @return mixed|null
     */
    public function scrapeOrigin($url) {        // "scrape KerndatenQuelle"

        // send request
        $response = $this->makeRequest($url);

        if ($response) {
            $datasources = $this->processOriginResponse((string)$response->getBody());

            if (!isset($datasources['item'])) {
                return null; // todo error handling
            }

            // be careful about structure here, if there is only one item returned
            // it is not wrapped in an array, do that manually
            if (!isset($datasources['item']['@attributes'])) {
                // default case: multiple items, first level is the wrapping array
                return $datasources['item'];
            }

            // exception: only one item was returned, wrap it
            return [ $datasources['item'] ];
        }

        return null;
    }

    /**
     * Scrape a given datasource url (Kerndaten-Satz)
     *
     * @param $parent_reference_id
     * @param $reference_id
     * @param $url
     * @return int|mixed|null
     */
    public function scrapeDatasource($parent_reference_id, $reference_id, $url) {        // "scrape KerndatenSatz"
        $response = $this->makeRequest($url);

        if ($response) {
            $content = (string)$response->getBody();
            $version = $this->insertIntoResultsTable($parent_reference_id, $reference_id,$content);

            return $version;
        }

        return null;
    }

    protected function insertIntoResultsTable($parent_reference_id, $reference_id, $content) {
        // first check against db to find the last version (integer)
        $lastVersion = DB::table('scraper_results')
            ->where('parent_reference_id',$parent_reference_id)
            ->where('reference_id',$reference_id)
            ->max('version');

        if ($lastVersion) {
            $last = DB::table('scraper_results')
                ->where('parent_reference_id',$parent_reference_id)
                ->where('reference_id',$reference_id)
                ->where('version',$lastVersion)->first();

            if ($last->content === $content) {
                // nothing to do, we already have the exact same thing in our db
                return $lastVersion;
            }
        } else {
            $lastVersion = 0;
        }

        // do insert
        DB::table('scraper_results')->insert([
            [
                'parent_reference_id' => $parent_reference_id,
                'reference_id' => $reference_id,
                'version' => ++$lastVersion,
                'content' => $content,
                'created_at' => Carbon::now(),
            ],
        ]);

        return $lastVersion;
    }

    /**
     * Perform a single GET request
     *
     * @param $url
     * @return bool|mixed|null|\Psr\Http\Message\ResponseInterface
     */
    protected function makeRequest($url) {
        // check interval
        if ($this->last_request_at) {
            $threshold = $this->last_request_at->addSeconds($this->interval);

            if ($threshold->greaterThan(Carbon::now())) {
                $diff = Carbon::now()->diffInMicroseconds($threshold);
                usleep($diff);
            }
        }

        // make request
        $client = $this->client;
        $response = null;

        try {
            $this->last_request_at = Carbon::now();
            $response = $client->request('GET', $url);

            $this->logRequest($url, $response);

            return $response;

        } catch (RequestException $e) {
            // TODO improve error handling
            // e.g. use Handler Stack, to easily retry failed queries (e.g. endpoint was down)
            // see https://stackoverflow.com/questions/38614534/how-to-check-if-endpoint-is-working-when-using-guzzlehttp/38622219#38622219

            //dump($e->getRequest());

            $this->logRequest($url, $e->getResponse());
        }

        return null;
    }

    protected function logRequest($url, $response) {
        try {
            if ($this->useDbLog) {
                if ($response) {
                    $this->logRequestToDb(
                        $url,
                        $response->getStatusCode(),
                        (string)$response->getBody(),
                        $response->getHeaders()
                    );
                } else {
                    // should never happen
                    $this->logRequestToDb($url, 0, '', '');
                }
            }
        } catch (\Exception $ex) {
            // TODO should never happen, but it does, e.g. Wirtschaftskammer URLS
            // like https://apppool.wko.at/data/ab/2/KD_685DE68E-0419-41CB-8FFE-0AED95045BBF.xml buttt why?

            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());

            dump($response);
            if ($response) {
                dump((string)$response->getBody());
            }
            dd('Scraper Error. Exit.');
        }
    }

    protected function logRequestToDb($url, $statusCode, $content, $headers = null) {

        $headersString = '';

        if ($headers) {
            foreach ($headers as $name => $values) {
                $headersString.= $name . ': ' . implode(', ', $values) . "\r\n";
            }
        }

        DB::table('scraper_log')->insert([
            [
                'url' => $url,
                'status_code' => $statusCode,
                'response' => $content,
                'headers' => $headersString,
                'created_at' => Carbon::now(),
            ],
        ]);
    }


    protected function processOriginResponse($content) {
        // use simplexml for parsing xml document
        $xml = simplexml_load_string($content);

        // use json encode to transform to json
        $json = json_encode($xml);

        // use json decode to get an associative array
        $array = json_decode($json,TRUE);

        return $array;
    }

    /**
     * @deprecated
     * @param $content
     * @return mixed
     */
    protected function processDatasourceResponse($content) {
        // use simplexml for parsing xml document
        $xml = simplexml_load_string($content);

        // todo terminology ?!
        $type = $xml->getName(); // e.g. "KD_8_1_Z2"

        // use json encode to transform to json
        $json = json_encode($xml);

        // use json decode to get an associative array
        $array = json_decode($json,TRUE);

        // add type to result, use prefix to prevent name collision
        $array['FIF_TYPE'] = $type;

        return $array;
    }
}
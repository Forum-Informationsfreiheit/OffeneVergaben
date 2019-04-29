<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Scraper
{
    protected $client;
    protected $useDbLog = false;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function useDbLog($use) {
        $this->useDbLog = $use;
    }

    /**
     * Scrape a given origin url
     *
     * @param $url
     * @return mixed|null
     */
    public function scrapeOrigin($url) {

        // send request
        $response = $this->makeRequest($url);

        if ($response) {
            $datasets = $this->processOriginResponse((string)$response->getBody());

            // we only care about the item info, publisher info is already set in origin table
            if (isset($datasets['item'])) {
                return $datasets['item'];
            }
        }

        return null;
    }

    /**
     * Perform a single GET request
     *
     * @param $url
     * @return bool|mixed|null|\Psr\Http\Message\ResponseInterface
     */
    protected function makeRequest($url) {

        $client = $this->client;
        $response = null;

        try {
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
}
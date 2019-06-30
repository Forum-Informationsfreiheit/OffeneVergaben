<?php

namespace App\Console\Commands;

use App\Datasource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:test-xml {parent_reference_id} {reference_id} {version}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing Purposes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $result = DB::table('scraper_results')
            ->select(['content'])
            ->where('parent_reference_id',$this->argument('parent_reference_id'))
            ->where('reference_id',$this->argument('reference_id'))
            ->where('version',$this->argument('version'))
            ->first();

        if (!$result) {
            dd('no result');
        }

        dd($this->xmlToArray($result->content));
    }

    protected function xmlToArray($xmlString) {
        // use simplexml for parsing xml document
        $xml = simplexml_load_string($xmlString);

        // use json encode to transform to json
        $json = json_encode($xml);

        // use json decode to get an associative array
        $array = json_decode($json,TRUE);

        $type = $xml->getName(); // e.g. "KD_8_1_Z2"
        // add type to result, use prefix to prevent name collision
        $array['FIF_TYPE'] = $type;

        return $array;
    }
}

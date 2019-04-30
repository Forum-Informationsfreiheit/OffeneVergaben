<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test_timestamp', function () {
    $testDate = "2019-04-01T07:17:33.997";

    $original = \Carbon\Carbon::createFromTimeString($testDate);

    dump($original);

    $updated = $original->addHour();

    dd($updated);
});

Route::get('/test_version', function() {
    $res = \Illuminate\Support\Facades\DB::table('scraper_results')->where('guid','123')->max('version');

    dd($res);
});

Route::get('/test_xml', function() {
    $xmlString = '<kdq xmlns="http://www.brz.gv.at/eproc/kdq/20180626">
<header>
<publisher>BMBWF</publisher>
<contact-person>Vergabeadministration</contact-person>
<contact-email>vergabe@bmbwf.gv.at</contact-email>
</header>
<item id="1" lastmod="2019-04-04T11:36:26Z">
<url>
https://extapp.noc-science.at/apex/shibb/api/vergabe/1
</url>
</item>
</kdq>';

    $xml = simplexml_load_string($xmlString);

    // use json encode to transform to json
    $json = json_encode($xml);

    // use json decode to get an associative array
    $array = json_decode($json,TRUE);

    dump($array);

    dump($array['item']);

    dd(count($array['item']));
});
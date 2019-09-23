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

Route::get('/', 'PageController@frontpage');

Route::get('/test', function () {
    return view('public.test');
});

Route::get('/aufträge','DatasetController@index')->name('public::auftraege');
Route::get('/lieferanten','ContractorController@index')->name('public::lieferanten');
Route::get('/auftraggeber','OfferorController@index')->name('public::auftraggeber');

// reserved routes for dynamic page content, directly under domain (no other url prefix)
Route::get('/impressum',   'PageController@reserved');
Route::get('/datenschutz', 'PageController@reserved');
Route::get('/überuns',     'PageController@reserved');

Auth::routes();

// temporary Earlybird stuff, remove for production
Route::get('/origins','EarlyBirdController@origins');
Route::get('/datasets','EarlyBirdController@datasets');
Route::get('/datasets/{id}','EarlyBirdController@dataset');
Route::get('/bekanntgaben','EarlyBirdController@bekanntgaben');
Route::get('/cpvs','EarlyBirdController@cpvs');
Route::get('/cpvs/{id}','EarlyBirdController@cpv');
Route::get('/organizations/','EarlyBirdController@orgs');
Route::get('/organizations/{id}','EarlyBirdController@org');

// TEST routes, NOT in production env available
Route::group(['prefix' => 'test'], function () {
    if (App::environment('production')) {
        return;
    }

    Route::get('/typo', function () {
        return view('public.typography-testpage');
    });

    Route::get('/timestamp', function () {
        $testDate = "2019-04-01T07:17:33.997";

        $original = \Carbon\Carbon::createFromTimeString($testDate);

        dump($original);

        $updated = $original->addHour();

        dd($updated);
    });

    Route::get('/version', function() {
        $res = \Illuminate\Support\Facades\DB::table('scraper_results')->where('guid','123')->max('version');

        dd($res);
    });

    Route::get('/xml', function() {
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

    Route::get('/csv',function() {
        $data = array ('aaa,bbb,ccc,dddd',
            '123,456,789',
            '"aaa","bbb"');
        $fp = fopen(storage_path('data.csv'), 'w');
        foreach($data as $line){
            $val = explode(",",$line);
            fputcsv($fp, $val);
        }
        fclose($fp);
    });

    Route::get('/cpv',function() {
        $cpv = \App\CPV::find("03115100");

        dd($cpv);
    });
    Route::get('/nuts',function() {
        $nuts = \App\NUTS::find("AT111");

        dd($nuts);
    });
    Route::get('/decimals',function() {
        echo "String Input:".'<br>';
        echo '100 --> '.convert_number_to_cents("100").'<br>';
        echo '100,0 --> '.convert_number_to_cents("100,0").'<br>';
        echo '100.0 --> '.convert_number_to_cents("100.0").'<br>';
        echo '100,5 --> '.convert_number_to_cents("100,5").'<br>';
        echo '100.5 --> '.convert_number_to_cents("100.5").'<br>';
        echo '100,55 --> '.convert_number_to_cents("100,55").'<br>';
        echo '100.55 --> '.convert_number_to_cents("100.55").'<br>';
        echo '100,555 --> '.convert_number_to_cents("100,555").'<br>';
        echo '100.555 --> '.convert_number_to_cents("100.555").'<br>';

        echo "Integer Input:".'<br>';
        echo '100 --> '.convert_number_to_cents(100).'<br>';
        echo '5000 --> '.convert_number_to_cents(5000).'<br>';
        echo '90000 --> '.convert_number_to_cents(90000).'<br>';

        echo "Float Input:".'<br>';
        echo '100.12 --> '.convert_number_to_cents(100.12).'<br>';
        echo '100.123 --> '.convert_number_to_cents(100.123).'<br>';
        echo '0.12345 --> '.convert_number_to_cents(0.12345).'<br>';

    });
    Route::get('/types',function() {
        $types = [];

        $datasources = \App\Datasource::all();

        foreach($datasources as $datasource) {

            // quick and dirty get xml for datasource and parse it, so we can read the type
            $result = \Illuminate\Support\Facades\DB::table('scraper_results')
                ->where('parent_reference_id',$datasource->origin->reference_id)
                ->where('reference_id',$datasource->reference_id)
                ->where('version',$datasource->version_scraped)
                ->first();

            // xml to array
            $xml = simplexml_load_string($result->content);
            $type = $xml->getName(); // e.g. "KD_8_1_Z2"

            if (!isset($types[$type])) {
                $types[$type] = [ 'name' => $type, 'count' => 1 ];
            } else {
                $types[$type]['count']++;
            }

        }

        dd($types);
    });
    Route::get('/types',function() {
        $types = \App\DatasetType::find("KD_7_2_Z2");

        dd($types);
    });

    Route::get('/timediff',function() {
        $now = \Carbon\Carbon::now();

        usleep(1223456);

        $later = \Carbon\Carbon::now();

        dd(gmdate('H:i:s', $now->diffInSeconds($later)));
    });
});


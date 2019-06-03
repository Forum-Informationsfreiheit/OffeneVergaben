<?php

namespace App\Console\Commands;

use App\Dataset;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TemporaryExporter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:temp-export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Temporary Export command for rough dataset output.';

    protected $results = [];

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
        $this->info('Starting export job...');

        $datasets = Dataset::all();

        $this->info('Found '.count($datasets). ' Datasets');

        $idx = 0;

        foreach($datasets as $dataset) {
            $idx++;

            $this->results[] = $this->processOne($dataset);
        }

        $this->export();

        $this->info('Export job finished...');
    }

    public function processOne($dataset) {
        $origin = $dataset->origin;

        $content = $this->xmlToArray($this->getContent($dataset)->content);

        $result = $this->parseContent($dataset,$content);

        return $result;
    }

    protected function export() {
        $now = Carbon::now()->format('Y-m-d_Hi');
        $fp = fopen(storage_path('app/export_'.$now.'.csv'), 'w');

        $idx = 0;

        $headers = array_keys($this->results[0]);
        fputcsv($fp, $headers);

        foreach($this->results as $result){

            // check the result for arrays, should actually all be strings
            foreach($result as $key => $shouldBeAString) {
                if (!is_string($shouldBeAString)) {
                    Log::warning('Not a string!',[ 'idx' => $idx, 'key' => $key, 'shouldBeAString' => $shouldBeAString, 'result' => $result ]);
                }
            }

            try {
                fputcsv($fp, array_values($result));
            } catch(\Exception $ex) {
                Log::error('Exception on export.', ['idx' => $idx, 'message' => $ex->getMessage(), 'trace' => $ex->getTraceAsString()]);
                dump('exception!!! line '.$idx);
                dd($result);
            }

            $idx++;
        }
        fclose($fp);
    }

    protected function parseContent($dataset,$content) {
        $result = [];

        $result['TYPE'] = $content['FIF_TYPE'];

        $CONTRACTING_BODY = isset($content['CONTRACTING_BODY']) ? $content['CONTRACTING_BODY'] : null;

        if ($CONTRACTING_BODY) {
            if (isset($CONTRACTING_BODY['ADDRESS_CONTRACTING_BODY'])) {
                $ADDRESS = $CONTRACTING_BODY['ADDRESS_CONTRACTING_BODY'];

                $result['CB_ADDRESS_OFFICIALNAME'] = isset($ADDRESS['OFFICIALNAME']) ? $ADDRESS['OFFICIALNAME'] : '';
                $result['CB_ADDRESS_NATIONALID']   = isset($ADDRESS['NATIONALID']) && !is_array($ADDRESS['NATIONALID'])  ? $ADDRESS['NATIONALID'] : '';
                $result['CB_ADDRESS_PHONE']        = isset($ADDRESS['PHONE']) && !is_array($ADDRESS['PHONE']) ? $ADDRESS['PHONE'] : '';      // add array check (telefon item was in xml but no value)
                $result['CB_ADDRESS_E_MAIL']       = isset($ADDRESS['E_MAIL']) && !is_array($ADDRESS['E_MAIL']) ? $ADDRESS['E_MAIL'] : '';
                $result['CB_ADDRESS_CONTACT']      = isset($ADDRESS['CONTACT']) && !is_array($ADDRESS['CONTACT']) ? $ADDRESS['CONTACT'] : '';

            } else {
                dump($dataset->origin->reference_id);
                dump($dataset->reference_id);
                dump($CONTRACTING_BODY);
                dd("POSSIBLY MULTIPLE CONTRACTING BODIES!!! NOT YET IMPLEMENTED");
            }
        }

        $OBJECT_CONTRACT = isset($content['OBJECT_CONTRACT']) ? $content['OBJECT_CONTRACT'] : null;

        if ($OBJECT_CONTRACT) {
            $result['OC_CPV_MAIN'] = isset($OBJECT_CONTRACT['CPV_MAIN'])
                && isset($OBJECT_CONTRACT['CPV_MAIN']['CPV_CODE'])
                && isset($OBJECT_CONTRACT['CPV_MAIN']['CPV_CODE']['@attributes'])
                && isset($OBJECT_CONTRACT['CPV_MAIN']['CPV_CODE']['@attributes']['CODE'])
                ? $OBJECT_CONTRACT['CPV_MAIN']['CPV_CODE']['@attributes']['CODE'] : '';

            $result['OC_TITLE'] = '';
            if (isset($OBJECT_CONTRACT['TITLE']) && isset($OBJECT_CONTRACT['TITLE']['P'])) {
                $ps = $OBJECT_CONTRACT['TITLE']['P'];

                if (is_array($ps)) {
                    $ps = array_filter($ps,function($i) {
                        // possible array problem, empty last line, filter out
                        return !is_array($i);
                    });
                    $result['OC_TITLE'] = join(' ',$ps);
                } else {
                    $result['OC_TITLE'] = $OBJECT_CONTRACT['TITLE']['P'];
                }
            }
            // duplicate code for short description, refactor that
            $result['OC_SHORT_DESCR'] = '';
            if (isset($OBJECT_CONTRACT['SHORT_DESCR']) && isset($OBJECT_CONTRACT['SHORT_DESCR']['P'])) {
                $ps = $OBJECT_CONTRACT['SHORT_DESCR']['P'];
                if (is_array($ps)) {
                    $ps = array_filter($ps,function($i) {
                        // possible array problem, empty last line, filter out
                        return !is_array($i);
                    });
                    $result['OC_SHORT_DESCR'] = join(' ',$ps);
                } else {
                    $result['OC_SHORT_DESCR'] = $OBJECT_CONTRACT['SHORT_DESCR']['P'];
                }
            }

            $result['OC_REFERENCE_NUMBER'] =  isset($OBJECT_CONTRACT['REFERENCE_NUMBER'])
                && !is_array($OBJECT_CONTRACT['REFERENCE_NUMBER']) ? $OBJECT_CONTRACT['REFERENCE_NUMBER'] : '';

            $result['OC_TYPE_CONTRACT'] = isset($OBJECT_CONTRACT['TYPE_CONTRACT'])
                && isset($OBJECT_CONTRACT['TYPE_CONTRACT']['@attributes'])
                && isset($OBJECT_CONTRACT['TYPE_CONTRACT']['@attributes']['CTYPE'])
                ? $OBJECT_CONTRACT['TYPE_CONTRACT']['@attributes']['CTYPE'] : '';

            $result['OC_OBJECT_DESCR_NUTS'] = isset($OBJECT_CONTRACT['OBJECT_DESCR'])
                && isset($OBJECT_CONTRACT['OBJECT_DESCR']['NUTS'])
                && isset($OBJECT_CONTRACT['OBJECT_DESCR']['NUTS']['@attributes'])
                && isset($OBJECT_CONTRACT['OBJECT_DESCR']['NUTS']['@attributes']['CODE'])
                    ? $OBJECT_CONTRACT['OBJECT_DESCR']['NUTS']['@attributes']['CODE'] : '';

            $result['OC_DATE_START'] = isset($OBJECT_CONTRACT['OBJECT_DESCR'])
                && isset($OBJECT_CONTRACT['OBJECT_DESCR']['DATE_START'])
                ? $OBJECT_CONTRACT['OBJECT_DESCR']['DATE_START'] : '';
            $result['OC_DATE_END'] = isset($OBJECT_CONTRACT['OBJECT_DESCR'])
            && isset($OBJECT_CONTRACT['OBJECT_DESCR']['DATE_END'])
                ? $OBJECT_CONTRACT['OBJECT_DESCR']['DATE_END'] : '';
        }

        $ADDITIONAL_CORE_DATA = isset($content['ADDITIONAL_CORE_DATA']) ? $content['ADDITIONAL_CORE_DATA'] : null;

        if ($ADDITIONAL_CORE_DATA) {

            if (isset($ADDITIONAL_CORE_DATA['D_JUSTIFICATION']) && isset($ADDITIONAL_CORE_DATA['D_JUSTIFICATION']['P'])) {
                $ps = $ADDITIONAL_CORE_DATA['D_JUSTIFICATION']['P'];
                $result['ACD_D_JUSTIFICATION'] = $this->getStringFromParagraphs($ps);
            }

            $result['ACD_DATE_FIRST_PUBLICATION'] = isset($ADDITIONAL_CORE_DATA['DATE_FIRST_PUBLICATION']) ? $ADDITIONAL_CORE_DATA['DATE_FIRST_PUBLICATION'] : '';
            $result['ACD_DATETIME_LAST_CHANGE'] = isset($ADDITIONAL_CORE_DATA['DATETIME_LAST_CHANGE']) ? $ADDITIONAL_CORE_DATA['DATETIME_LAST_CHANGE'] : '';
            $result['ACD_OBJECT_CONTRACT_MODIFICATIONS_TITLE'] = isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS'])
                && isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TITLE'])
                && isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TITLE']['P'])
                ? $ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TITLE']['P'] : '';
            $result['ACD_OBJECT_CONTRACT_MODIFICATIONS_TYPE_CONTRACT'] = isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS'])
                && isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TYPE_CONTRACT'])
                && isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TYPE_CONTRACT']['@attributes'])
                && isset($ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TYPE_CONTRACT']['@attributes']['CTYPE'])
                ? $ADDITIONAL_CORE_DATA['OBJECT_CONTRACT_MODIFICATIONS']['TYPE_CONTRACT']['@attributes']['CTYPE'] : '';

            $result['ACD_PROCEDURE_SHORT_DESCRIPTION'] = isset($ADDITIONAL_CORE_DATA['PROCEDURE_SHORT_DESCRIPTION'])
                && isset($ADDITIONAL_CORE_DATA['PROCEDURE_SHORT_DESCRIPTION']['P'])
                 ? $ADDITIONAL_CORE_DATA['PROCEDURE_SHORT_DESCRIPTION']['P'] : '';

            // TODO are these things bools ?
            //$result['ACD_BELOWTHRESHOLD'] = isset($ADDITIONAL_CORE_DATA['BELOWTHRESHOLD']) ? $ADDITIONAL_CORE_DATA['BELOWTHRESHOLD'] : '';
            $result['ACD_BELOWTHRESHOLD'] = isset($ADDITIONAL_CORE_DATA['BELOWTHRESHOLD']) ? 'ja' : '';
            //$result['ACD_ABOVETHRESHOLD'] = isset($ADDITIONAL_CORE_DATA['ABOVETHRESHOLD']) ? $ADDITIONAL_CORE_DATA['ABOVETHRESHOLD'] : '';
            $result['ACD_ABOVETHRESHOLD'] = isset($ADDITIONAL_CORE_DATA['ABOVETHRESHOLD']) ? 'ja' : '';

            $result['PROCEDURE_TYPE'] = '';
            if (isset($content['PROCEDURE'])) {
                $procedure = $content['PROCEDURE'];
                $procedureTypes = [];

                if (isset($procedure['PT_OPEN'])) { $procedureTypes[] = 'PT_OPEN'; }
                if (isset($procedure['PT_RESTRICTED'])) { $procedureTypes[] = 'PT_RESTRICTED'; }
                if (isset($procedure['PT_WITH_PRIOR_NOTICE'])) { $procedureTypes[] = 'PT_WITH_PRIOR_NOTICE'; }
                if (isset($procedure['PT_WITHOUT_PRIOR_NOTICE'])) { $procedureTypes[] = 'PT_WITHOUT_PRIOR_NOTICE'; }
                if (isset($procedure['PT_COMPETITIVE_NEGOTIATION'])) { $procedureTypes[] = 'PT_COMPETITIVE_NEGOTIATION'; }
                if (isset($procedure['PT_COMPETITIVE_DIALOGUE'])) { $procedureTypes[] = 'PT_COMPETITIVE_DIALOGUE'; }
                if (isset($procedure['PT_INNOVATION_PARTNERSHIP'])) { $procedureTypes[] = 'PT_INNOVATION_PARTNERSHIP'; }
                if (isset($procedure['PT_SPECIAL_SERVICE'])) { $procedureTypes[] = 'PT_SPECIAL_SERVICE'; }
                if (isset($procedure['PT_DIRECT'])) { $procedureTypes[] = 'PT_DIRECT'; }
                if (isset($procedure['DPS'])) { $procedureTypes[] = 'DPS'; }

                if (count($procedureTypes)) {
                    $result['PROCEDURE_TYPE'] = join(', ',$procedureTypes);
                }
            }

        }

        $RESULTS = isset($content['RESULTS']) ? $content['RESULTS'] : null;

        $result['AP_PARTICIPANTS'] = $RESULTS
            && isset($RESULTS['AWARDED_PRIZE'])
            && isset($RESULTS['AWARDED_PRIZE']['NB_PARTICIPANTS'])
            ? $RESULTS['AWARDED_PRIZE']['NB_PARTICIPANTS'] : '';
        $result['AP_WINNER_OFFICIALNAME'] = $RESULTS
            && isset($RESULTS['AWARDED_PRIZE'])
            && isset($RESULTS['AWARDED_PRIZE']['WINNER'])
            && isset($RESULTS['AWARDED_PRIZE']['WINNER']['ADDRESS_WINNER'])
            && isset($RESULTS['AWARDED_PRIZE']['WINNER']['ADDRESS_WINNER']['OFFICIALNAME'])
            ? $RESULTS['AWARDED_PRIZE']['WINNER']['ADDRESS_WINNER']['OFFICIALNAME'] : '';
        $result['AP_VAL_PRIZE'] = $RESULTS
            && isset($RESULTS['AWARDED_PRIZE'])
            && isset($RESULTS['AWARDED_PRIZE']['VAL_PRIZE'])
            ? $RESULTS['AWARDED_PRIZE']['VAL_PRIZE'] : '';

        $AC = isset($content['AWARD_CONTRACT']) && isset($content['AWARD_CONTRACT']['AWARDED_CONTRACT'])
            ? $content['AWARD_CONTRACT']['AWARDED_CONTRACT'] : null;

        $result['AC_DATE_CONCLUSION_CONTRACT'] = $AC && isset($AC['DATE_CONCLUSION_CONTRACT']) ? $AC['DATE_CONCLUSION_CONTRACT'] : '';
        $result['AC_NB_TENDERS_RECEIVED']      = $AC && isset($AC['NB_TENDERS_RECEIVED']) ? $AC['NB_TENDERS_RECEIVED'] : '';
        $result['AC_NB_SME_TENDER']            = $AC && isset($AC['NB_SME_TENDER']) ? $AC['NB_SME_TENDER'] : '';

        $result['AC_VAL_TOTAL'] = $AC && isset($AC['VAL_TOTAL']) && !is_array($AC['VAL_TOTAL']) ? $AC['VAL_TOTAL'] : '';    // potentially wrong, siehe id 1647

        $result['AC_CONTRACTOR_OFFICIALNAME'] = $AC && isset($AC['CONTRACTOR'])
            && isset($AC['CONTRACTOR']['ADDRESS_CONTRACTOR'])
            && isset($AC['CONTRACTOR']['ADDRESS_CONTRACTOR']['OFFICIALNAME'])
            ? $AC['CONTRACTOR']['ADDRESS_CONTRACTOR']['OFFICIALNAME'] : '';
        $result['AC_CONTRACTOR_NATIONALID'] = $AC && isset($AC['CONTRACTOR'])
        && isset($AC['CONTRACTOR']['ADDRESS_CONTRACTOR'])
        && isset($AC['CONTRACTOR']['ADDRESS_CONTRACTOR']['NATIONALID'])
        && !is_array($AC['CONTRACTOR']['ADDRESS_CONTRACTOR']['NATIONALID']) // empty value check
            ? $AC['CONTRACTOR']['ADDRESS_CONTRACTOR']['NATIONALID'] : '';

        return $result;
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

    protected function getContent($dataset) {
        $content = DB::table('scraper_results')
            ->where('parent_reference_id',$dataset->origin->reference_id)
            ->where('reference_id',$dataset->reference_id)
            ->where('version',$dataset->version_scraped)
            ->first();

        return $content;
    }

    protected function getStringFromParagraphs($ps) {
        $string = '';

        if (is_array($ps)) {
            $ps = array_filter($ps,function($i) {
                // possible array problem, empty last line, filter out
                return !is_array($i);
            });
            $string = join(' ',$ps);
        } else {
            $string = $ps;
        }

        return $string;
    }
}

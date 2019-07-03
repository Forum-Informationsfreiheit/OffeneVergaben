<?php

namespace App\Jobs;

use App\Contractor;
use App\CPV;
use App\Dataset;
use App\Datasource;
use App\DataSourcePreProcessor;
use App\NUTS;
use App\Offeror;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;

class Process implements ShouldQueue
{
    use Dispatchable;

    protected $timestamp;

    protected $preProcessor;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // use same timestamp for all requests
        $this->timestamp = Carbon::now();

        $this->preProcessor = new DataSourcePreProcessor();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // TODO use block sized reading/writing of data (so we dont run into memory issues)

        // Get only unprocessed data sources
        $sources = Datasource::unprocessed()->get();

        //$sources = Datasource::where('id',1809)->get();  --> with contracting bodies
        //$sources = Datasource::where('id',1344)->get();  --> FEHLER, falscher NUTS CODE

        dump($sources->count());

        $stopAt = 100;
        $idx = 0;

        foreach($sources as $source) {
            $this->processDataSource($source);

            if (++$idx >= $stopAt) {
                //break;
            }
        }

        dd('Exit Processing');
    }

    protected function processDataSource($source) {

        $content = $source->content;

        $this->preProcessor->preProcess($content);

        $data = $this->preProcessor->getData();

        /*
        //dd($data);
        if ($data->awardContract && count($data->awardContract->contractors) > 1) {
            dd($data);
        }

        if ($data->modificationsContract && count($data->modificationsContract->contractors) > 1) {
            dd($data);
        }

        if ($data->contractingBody->additional && count($data->contractingBody->additional) == 1) {
            dd($data);
        }
        */

        // Validate foreign key values before attempting to write
        $checkCPVCode  = $data->objectContract->cpv  ? CPV::find($data->objectContract->cpv) : true;
        $checkNUTSCode = $data->objectContract->nuts ? NUTS::find($data->objectContract->nuts) : true;

        if (!$checkCPVCode) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Unknown CPV #'.$data->objectContract->cpv.'#');
        }

        if (!$checkNUTSCode) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Unknown NUTS #'.$data->objectContract->nuts.'#');
        }

        if (!$checkCPVCode || !$checkNUTSCode) {
            return;
        }

        // write dataset
        try {

            // use db transaction as we have to fill multiple database tables
            DB::beginTransaction();

            // Handle root dataset
            $dataset = new Dataset();
            $dataset->datasource_id = $source->id;
            $dataset->version = $source->version_scraped;

            $dataset->type_code = $data->type;

            $dataset->cpv_code = $data->objectContract->cpv;
            $dataset->nuts_code = $data->objectContract->nuts;

            $dataset->save();

            // Handle main offeror
            $offeror = new Offeror();
            $offeror->dataset_id = $dataset->id;
            $offeror->is_extra = false;
            $offeror->national_id = $data->contractingBody->nationalId;
            $offeror->name = $data->contractingBody->officialName;
            $offeror->phone = $data->contractingBody->phone;
            $offeror->email = $data->contractingBody->email;
            $offeror->contact = $data->contractingBody->contact;
            $offeror->domain = $data->contractingBody->domain;
            $offeror->save();

            // Handle additional offerors
            if ($data->contractingBody->additional) {
                foreach($data->contractingBody->additional as $additional) {
                    $offeror = new Offeror();
                    $offeror->dataset_id = $dataset->id;
                    $offeror->is_extra = true;
                    $offeror->national_id = $additional->nationalId;
                    $offeror->name = $additional->officialName;
                    $offeror->phone = $additional->phone;
                    $offeror->email = $additional->email;
                    $offeror->contact = $additional->contact;
                    $offeror->domain = $additional->domain;
                    $offeror->save();
                }
            }

            // Handle contractors
            // AWARD contractors
            if ($data->awardContract && $data->awardContract->contractors) {
                foreach($data->awardContract->contractors as $ac) {
                    $contractor = new Contractor();
                    $contractor->dataset_id = $dataset->id;
                    $contractor->national_id = $ac->nationalId;
                    $contractor->name = $ac->officialName;
                    $contractor->save();
                }
            }
            // MODIFICATIONS contractors
            if ($data->modificationsContract && $data->modificationsContract->contractors) {
                foreach($data->modificationsContract->contractors as $mc) {
                    $contractor = new Contractor();
                    $contractor->dataset_id = $dataset->id;
                    $contractor->national_id = $mc->nationalId;
                    $contractor->name = $mc->officialName;
                    $contractor->save();
                }
            }
            // , and "Winners" TODO! PREPROCESSOR!!!!

            DB::commit();

        } catch(\Exception $ex) {
            Log::error($ex->getCode() . ' ' . $ex->getMessage());
            Log::error($ex->getTraceAsString());

            dump('Unable to write dataset for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump($data);

            DB::rollBack();
        }
    }
}

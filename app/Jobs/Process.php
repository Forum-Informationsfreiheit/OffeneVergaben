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

    protected $recordIds;

    protected $touchedDatasources = [];

    /**
     *
     */
    public function __construct()
    {
        // use same timestamp for all requests
        $this->timestamp = Carbon::now();

        $this->preProcessor = new DataSourcePreProcessor();

        $this->recordIds = $this->getRecordIds();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dump(count($this->recordIds)." to be processed...");

        // Use "block sized" processing to prevent any kind of memory issues
        $blockSize = 200;
        $index = 0;
        $records = $this->getRecords(0, $blockSize);

        while(count($records) > 0) {
            dump("processing blocked records, index:$index, records:".(count($records)));

            foreach($records as $record) {
                // preprocess source xml
                $this->preProcessor->preProcess($record->content);

                // actually do some processing
                $data = $this->preProcessor->getData();

                $this->process($record,$data);
            }

            // prepare for next iteration
            $index += count($records);
            $records = $this->getRecords($index, $blockSize);
        }

        // finally do some housekeeping
        // update the datasource table with info of the last processed version
        dump("Finalizing...");
        $this->updateDatasources();
    }

    protected function process($record,$data) {

        // fetch datasource for record
        $source = Datasource::where('origin_id',$record->origin_id)
            ->where('reference_id',$record->reference_id)->first();

        // Validate foreign key values before attempting to write
        $validationError = false;

        $checkCPVCode  = $data->objectContract->cpv  ? CPV::find($data->objectContract->cpv) : true;
        $checkNUTSCode = $data->objectContract->nuts ? NUTS::find($data->objectContract->nuts) : true;

        if (!$checkCPVCode) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Unknown CPV #'.$data->objectContract->cpv.'#');
            $validationError = true;
        }

        if (!$checkNUTSCode) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Unknown NUTS #'.$data->objectContract->nuts.'#');
            $validationError = true;
        }

        if ($data->contractingBody->urlDocumentIsRestricted && $data->contractingBody->urlDocumentIsFull) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Both Document Flags (RESTRICTED&FULL) set instead of one or none');
            $validationError = true;
        }

        if ($data->objectContract->lot && $data->objectContract->noLot) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Both LOT Flags (LOT_DIVISION&NO_LOT_DIVISION) set instead of one or none');
            $validationError = true;
        }

        if ($data->additionalCoreData && $data->additionalCoreData->belowThreshold
                && $data->additionalCoreData->aboveThreshold) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Both THRESHOLD Flags (ABOVE&BELOW) set instead of one or none');
            $validationError = true;
        }

        if ($data->objectContract->additionalCpvs) {
            $uniqueAdditionalCpvs = array_unique($data->objectContract->additionalCpvs);
            $checkAddCpvs = CPV::whereIn('code',$data->objectContract->additionalCpvs)->get()->pluck('code')->all();

            // Check if we could find all referenced in db, otherwise error
            if (count($uniqueAdditionalCpvs) != count($checkAddCpvs)) {
                dump('Failed validation for datasource (id:'.$source->id.') to db.');
                dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
                dump('At least one unknown additional cpv code.',$data->objectContract->additionalCpvs);
                $validationError = true;
            }
        }

        if ($data->additionalCoreData && $data->additionalCoreData->nbSmeContractor
                && $data->awardContract && $data->awardContract->nbSmeContractor) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Both NB_SME_CONTRACTOR attributes are set (additional core data & award contract)!!');
            $validationError = true;
        }

        if ($data->modificationsContract && $data->modificationsContract->cpv && $data->objectContract->cpv
                && $data->modificationsContract->cpv != $data->objectContract->cpv) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Differing CPV Codes in OBJECT_CONTRACT and MODIFICATIONS_CONTRACT !!');
            $validationError = true;
        }

        if ($data->modificationsContract && $data->modificationsContract->additionalNeed
            && $data->modificationsContract->unforeseenCircumstance) {
            dump('Failed validation for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump('Both INFO_MODIFICATIONS text fields are set (additional_need & unforeseen_circumstance) !!');
            $validationError = true;
        }

        if ($validationError) {
            return;
        }

        // write dataset
        try {

            // use db transaction as we have to fill multiple database tables
            DB::beginTransaction();

            // Handle root dataset
            $dataset = new Dataset();
            $dataset->result_id = $record->id;
            $dataset->datasource_id = $source->id;
            $dataset->version = $record->version;

            $dataset->type_code = $data->type;

            $dataset->cpv_code = $data->objectContract->cpv;
            $dataset->nuts_code = $data->objectContract->nuts;

            $dataset->url_participation = $data->contractingBody->urlParticipation;
            $dataset->url_document = $data->contractingBody->urlDocument;
            $dataset->url_is_restricted = $data->contractingBody->urlDocumentIsRestricted ? 1 :
                ($data->contractingBody->urlDocumentIsFull ? 0 : null); // put restricted OR full OR none info into one attribute

            // data from object contract
            $dataset->contract_type = $data->objectContract->type;
            $dataset->title = $data->objectContract->title;
            $dataset->description = $data->objectContract->description;
            $dataset->date_start = $data->objectContract->dateStart;
            $dataset->date_end = $data->objectContract->dateEnd;
            $dataset->duration = $data->objectContract->duration;
            $dataset->datetime_receipt_tenders = $data->procedures ? $data->procedures->dateTimeReceiptTenders : null;
            $dataset->is_lot = $data->objectContract->lot ? 1 : ($data->objectContract->noLot ? 0 : null);

            // ADDITIONAL CORE DATA
            if ($data->additionalCoreData) {
                $dataset->justification = $data->additionalCoreData->justification;
                $dataset->date_first_publication = $data->additionalCoreData->dateFirstPublication;
                $dataset->datetime_last_change = $data->additionalCoreData->dateTimeLastChange;
                $dataset->deadline_standstill = $data->additionalCoreData->deadlineStandstill;
                $dataset->rd_notification = $data->additionalCoreData->rdNotification;
                $dataset->nb_sme_contractor = $data->additionalCoreData->nbSmeContractor;

                if ($data->additionalCoreData->objectContractModifications) {
                    $dataset->ocm_title = $data->additionalCoreData->objectContractModifications->title;
                    $dataset->ocm_contract_type = $data->additionalCoreData->objectContractModifications->type;
                }

                $dataset->nb_sme_contractor = $data->additionalCoreData->nbSmeContractor;
                $dataset->procedure_description = $data->additionalCoreData->procedureDescription;
                $dataset->threshold = $data->additionalCoreData->aboveThreshold ? 1 :
                    ($data->additionalCoreData->belowThreshold ? 0 : null);
                $dataset->url_revocation = $data->additionalCoreData->urlRevocation;
                $dataset->url_revocation_statement = $data->additionalCoreData->urlRevocationStatement;
            }

            if ($data->awardedPrize) {
                $dataset->nb_participants = $data->awardedPrize->nbParticipants;
                $dataset->nb_participants_sme = $data->awardedPrize->nbParticipantsSme;
                $dataset->val_total = convert_number_to_cents($data->awardedPrize->valPrize);
            }

            if ($data->awardContract) {
                $dataset->date_conclusion_contract = $data->awardContract->dateConclusionContract;
                $dataset->nb_tenders_received = $data->awardContract->nbTendersReceived;
                $dataset->nb_sme_tender = $data->awardContract->nbSmeTender;
                $dataset->nb_sme_contractor = $data->awardContract->nbSmeContractor;
                $dataset->val_total = convert_number_to_cents($data->awardContract->valTotal);
            }

            if ($data->modificationsContract) {
                $dataset->cpv_code = $data->modificationsContract->cpv; // validation makes sure we don't falsely overwrite
                $dataset->val_total_before = convert_number_to_cents($data->modificationsContract->valTotalBefore);
                $dataset->val_total_after = convert_number_to_cents($data->modificationsContract->valTotalAfter);
                $dataset->info_modifications = $data->modificationsContract->additionalNeed ?
                    $data->modificationsContract->additionalNeed :
                    ($data->modificationsContract->unforeseenCircumstance ?
                        $data->modificationsContract->unforeseenCircumstance : null);
            }

            $dataset->save();

            // handle (additional) cpvs
            $dataset->cpvs()->attach($dataset->cpv_code,['main' => 1]);

            if ($data->objectContract->additionalCpvs) {
                // make sure we only store unique additional cpv codes
                // they have to be different from the main cpv code (which is already stored at this point)
                $additionalCpvsFiltered = array_filter(array_unique($data->objectContract->additionalCpvs),
                    function($elem) use($dataset) {
                        return $elem != $dataset->cpv_code;
                    });

                foreach($additionalCpvsFiltered as $additionalCpv) {
                    $dataset->cpvs()->attach($additionalCpv,['main' => 0]);
                }
            }

            // handle procedures
            if ($data->procedures && $data->procedures->procedures) {

                foreach($data->procedures->procedures as $procedure) {
                    $dataset->procedures()->attach($procedure);
                }
            }

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

            // store info with offeror, but get it from objectContract
            $offeror->reference_number = $data->objectContract->refNumber;

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
                    $offeror->reference_number = $additional->refNumber;
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
            // AWARDED PRIZE winners (=contractors)~
            if ($data->awardedPrize && $data->awardedPrize->winners) {
                foreach($data->awardedPrize->winners as $w) {
                    $contractor = new Contractor();
                    $contractor->dataset_id = $dataset->id;
                    $contractor->national_id = $w->nationalId;
                    $contractor->name = $w->officialName;
                    $contractor->save();
                }
            }

            DB::commit();

            // keep the information which datasources we processed
            if (!isset($this->touchedDatasources[$source->id])) {
                $this->touchedDatasources[$source->id] = 1;
            }

        } catch(\Exception $ex) {
            Log::error($ex->getCode() . ' ' . $ex->getMessage());
            Log::error($ex->getTraceAsString());

            dump('Unable to write dataset for datasource (id:'.$source->id.') to db.');
            dump('reference_id:'.$source->reference_id . ' ORIGIN reference_id:'.$source->origin->reference_id);
            dump($data);

            DB::rollBack();
        }
    }

    /**
     * Set the highest processed version on the datasource objects
     */
    protected function updateDatasources() {

        $ids = array_keys($this->touchedDatasources);

        foreach($ids as $id) {

            // get the highest version number of processed datasets for a given datasource
            $query = DB::table('datasets')
                ->where('datasource_id',$id);

            $version = $query->max('version');

            // set the maximum version as the current version
            $datasource = Datasource::find($id);
            $datasource->version = $version;
            $datasource->save();
        }
    }

    /**
     * Default: Get Record Ids of All Unprocessed records
     *          (= no processed dataset exists for a given record id)
     *
     * Future:  NOT YET IMPLEMENTED
     *          Handle parameterized record ids,
     *          e.g. only process a single provided id, or a given array of ids
     */
    protected function getRecordIds() {

        $getAllUnprocessedRecords = true;       // TODO parameterized ids

        if ($getAllUnprocessedRecords) {
            $query = DB::table('scraper_results AS res')
                ->select('res.id as id')
                ->leftJoin('datasets', 'res.id', '=', 'datasets.result_id')
                ->where('datasets.id','=',null)
                ->orderBy('res.id','asc');

            return $query->pluck('id')->toArray();
        }

        return [];
    }

    public function getRecords($offset = null, $limit = null) {

        $ids = array_slice($this->recordIds,$offset,$limit);

        // get scraper results
        // where no processed dataset exists

        $select = [
            'res.id','res.parent_reference_id','o.id as origin_id',
            'res.reference_id', 'res.version','res.content'
        ];

        $query = DB::table('scraper_results AS res')
            ->select($select)
            ->join('origins AS o','res.parent_reference_id', '=', 'o.reference_id')
            ->whereIn('res.id',$ids)
            ->orderBy('res.id','asc');

        $result = $query->get();

        return $result;
    }
}

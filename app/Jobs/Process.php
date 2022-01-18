<?php

namespace App\Jobs;

use App\Contractor;
use App\CPV;
use App\Dataset;
use App\DOMKerndaten;
use App\Metaset;
use App\NationalIdParser;
use App\NUTS;
use App\Offeror;
use App\Organization;
use App\ScraperKerndaten;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Process
{
    use Dispatchable;

    protected $timestamp;

    protected $recordIds;

    protected $log;

    /**
     * Constructs the new processing Job, handle method will be fired on dispatch.
     *
     * @param array $ids - an array of kerndaten ids as referencing the id column in the scraper database table 'kerndaten'
     */
    public function __construct($ids)
    {
        $this->recordIds = $ids;

        // use same timestamp for all requests
        $this->timestamp = Carbon::now();

        // setup explicit processor log
        $this->log = Log::channel('processor_daily');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dump(             'Process ' . count($this->recordIds)." kerndaten");
        $this->log->debug('Process ' . count($this->recordIds)." kerndaten");

        // Use "block sized" processing to prevent any kind of memory issues
        $blockSize = 50;
        $index = 0;

        // Collection of App\ScraperKerndaten
        $records = $this->getRawKerndatenRecords(0, $blockSize);

        $processedCount = 0;

        while(count($records) > 0) {
            dump(             "Process block info: index:".($index+1).", length:".(count($records)));
            $this->log->debug("Process block info: index:".($index+1).", length:".(count($records)));

            foreach($records as $record) {
                dump("Processing Record ID {$record->id}");
		$this->log->debug("Processing Record ID {$record->id}");    
		$success = 0;

		try {
			$dom = new DOMKerndaten($record->xml,null,[ 'record_id' => $record->id ]);

			$success = $this->process($record,$dom->getData());
		} catch(\Exception $ex) {
			$this->log->debug("Failed Parsing Record ID {$record->id}");
			$this->log->debug($ex);
		}

                $processedCount += $success ? 1 : 0;
            }

            // dump('********************** NEXT BLOCK ********************');

            // prepare for next iteration
            $index += count($records);
            $records = $this->getRawKerndatenRecords($index, $blockSize);
        }

        dump(             "Processed $processedCount records.");
        $this->log->debug("Processed $processedCount records.");
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return array of Kerndaten Models
     */
    protected function getRawKerndatenRecords($offset = null, $limit = null) {
        $ids = array_slice($this->recordIds,$offset,$limit);

        $data = ScraperKerndaten::whereIn('id',$ids)->get();

        return $data;
    }

    /**
     * MAIN processing logic.
     *
     * @param $record
     * @param $data
     *
     * @return boolean
     */
    protected function process($record,$data) {

        try {
            // Skip record if validation fails
            if (!$this->validate($record, $data)) {
                return false;
            }

            // WRITE dataset -------------------------------------------------------------------------------------------
            // use db transaction as we have to fill multiple database tables
            DB::beginTransaction();

            // Create metaset on fresh dataset, or use existing for updates
            $metaset = Metaset::where('quellen_id',$record->rel_quelle->id)->where('item_id',$record->item_id)->first();
            if (!$metaset) {
                $metaset = new Metaset();
                $metaset->quellen_id = $record->rel_quelle->id;
                $metaset->item_id = $record->item_id;
                $metaset->save();
            }

            // Handle root dataset
            $dataset = new Dataset();
            $dataset->metaset_id = $metaset->id;
            // 2021-12-15 item_lastmod is timestamp with microseconds and not a date - use accessor/mutator
            //$dataset->item_lastmod = $record->item_lastmod;
            if ($record->item_lastmod) {
                $dataset->item_lastmod = Carbon::createFromFormat('Y-m-d H:i:s.u', $record->item_lastmod);
            }
            $dataset->scraper_kerndaten_id = $record->id;
            $dataset->version = $record->version;
            $dataset->is_current_version = 1;          // confidently set the newest dataset as the current version

            $dataset->type_code = $data->type;
            $dataset->cpv_code = $data->objectContract->cpv;
            $dataset->nuts_code = $data->objectContract->nuts;
            $dataset->url_participation = $data->contractingBody->urlParticipation;
            $dataset->url_document = $data->contractingBody->urlDocument;
            $dataset->url_is_restricted = $data->contractingBody->urlDocumentIsRestricted ? 1 :
                ($data->contractingBody->urlDocumentIsFull ? 0 : null); // put restricted OR full OR none info into one attribute

            // OBJECT_CONTRACT (mostly)
            $dataset->contract_type = $data->objectContract->type;
            $dataset->title = $data->objectContract->title;
            $dataset->description = $data->objectContract->description;
            $dataset->date_start = $data->objectContract->dateStart;
            $dataset->date_end = $data->objectContract->dateEnd;
            $dataset->duration = $data->objectContract->duration;
            $dataset->is_lot = $data->objectContract->lot ? 1 : ($data->objectContract->noLot ? 0 : null);

            if ($data->procedure) {
                $dataset->datetime_receipt_tenders = $data->procedure->dateTimeReceiptTenders;
                $dataset->is_framework = $data->procedure->isFramework;
            }

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

            if ($data->awardContract) {
                $dataset->date_conclusion_contract = $data->awardContract->dateConclusionContract;
                $dataset->nb_tenders_received = $data->awardContract->nbTendersReceived;
                $dataset->nb_sme_tender = $data->awardContract->nbSmeTender;
                $dataset->nb_sme_contractor = $data->awardContract->nbSmeContractor;
                $dataset->val_total = convert_number_to_cents($data->awardContract->valTotal);
            }

            if ($data->results) {
                $dataset->nb_participants = $data->results->nbParticipants;
                $dataset->nb_participants_sme = $data->results->nbParticipantsSme;
                $dataset->val_total = convert_number_to_cents($data->results->valPrize);
            }

            if ($data->modificationsContract) {
                $dataset->cpv_code = $data->modificationsContract->cpv; // validation makes sure we don't falsely overwrite
                $dataset->val_total_before = convert_number_to_cents($data->modificationsContract->valTotalBefore);
                $dataset->val_total_after = convert_number_to_cents($data->modificationsContract->valTotalAfter);
                $dataset->info_modifications = $data->modificationsContract->additionalNeed ?
                    $data->modificationsContract->additionalNeed :
                    ($data->modificationsContract->unforeseenCircumstance ?
                        $data->modificationsContract->unforeseenCircumstance : null);

                // tested with ~20k datasets, no positive case so far. keep it anyway
                if (!$dataset->description && $data->modificationsContract->description) {
                    $dataset->description = $data->modificationsContract->description;
                }
            }

            $dataset->save();

            // handle (additional) cpvs
            $dataset->cpvs()->attach($dataset->cpv_code,['main' => 1]);

            if (count($data->objectContract->additionalCpvs) > 0) {
                // make sure we only store unique additional cpv codes
                // they have to be different from the main cpv code (which is already stored at this point)
                $additionalCpvsFiltered = array_filter(array_unique($data->objectContract->additionalCpvs),
                    function($elem) use($dataset) {
                        return $elem != $dataset->cpv_code;
                    });

                foreach($additionalCpvsFiltered as $additionalCpv) {
                    $dataset->cpvs()->attach($additionalCpv,['main' => 0]);
                }
            } else if($data->modificationsContract && count($data->modificationsContract->additionalCpvs) > 0) {
                $additionalCpvsFiltered = array_filter(array_unique($data->modificationsContract->additionalCpvs),
                    function($elem) use($dataset) {
                        return $elem != $dataset->cpv_code;
                    });

                foreach($additionalCpvsFiltered as $additionalCpv) {
                    $dataset->cpvs()->attach($additionalCpv,['main' => 0]);
                }
            }

            // PROCEDURES
            if ($data->procedure) {
                foreach($data->procedure->procedures as $procedure) {
                    $dataset->procedures()->attach($procedure);
                }
            }

            // Handle main offeror
            $offeror = new Offeror();
            $offeror->dataset_id = $dataset->id;
            $offeror->is_extra = false;
            $offeror->national_id = $data->contractingBody->address->nationalId;
            $offeror->name = $data->contractingBody->address->officialName;
            $offeror->phone = $data->contractingBody->address->phone;
            $offeror->email = $data->contractingBody->address->email;
            $offeror->contact = $data->contractingBody->address->contact;
            $offeror->domain = $data->contractingBody->address->domain;
            // main offeror reference number is stored in object contract (additionals directly in address)
            $offeror->reference_number = $data->objectContract->refNumber;

            // Handle organization
            // Try to match the record to an existing organization
            // or create a new one if there is no match
            $organization = $this->matchOrCreateOrganization($offeror->national_id, $offeror->name);
            $offeror->organization_id = $organization->id;

            $offeror->save();


            // Handle additional offerors
            foreach($data->contractingBody->addressAdditional as $additional) {
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

                $organization = $this->matchOrCreateOrganization($offeror->national_id, $offeror->name);
                $offeror->organization_id = $organization->id;

                $offeror->save();
            }

            $organizations = [];

            // Handle contractors
            // AWARD contractors

            // keep track of contractors, treat the first contractor as 'main', others as 'extra'
            $countContractors = 0;

            if ($data->awardContract && $data->awardContract->contractors) {
                foreach($data->awardContract->contractors as $ac) {
                    $organization = $this->matchOrCreateOrganization($ac->nationalId, $ac->officialName);

                    // restrict the saved contractors so that
                    // the same organization is only used once per dataset
                    if ($organization && !in_array($organization->id,$organizations)) {
                        $organizations[] = $organization->id;

                        $contractor = new Contractor();
                        $contractor->dataset_id = $dataset->id;
                        $contractor->national_id = $ac->nationalId;
                        $contractor->name = $ac->officialName;
                        $contractor->organization_id = $organization ? $organization->id : null;
                        $contractor->is_extra = $countContractors > 0;
                        $contractor->save();

                        $countContractors++;
                    }
                }
            }

            // MODIFICATIONS contractors
            if ($data->modificationsContract && $data->modificationsContract->contractors) {
                foreach($data->modificationsContract->contractors as $mc) {
                    $organization = $this->matchOrCreateOrganization($mc->nationalId, $mc->officialName);

                    // restrict the saved contractors so that
                    // the same organization is only used once per dataset
                    if ($organization && !in_array($organization->id,$organizations)) {
                        $organizations[] = $organization->id;

                        $contractor = new Contractor();
                        $contractor->dataset_id = $dataset->id;
                        $contractor->national_id = $mc->nationalId;
                        $contractor->name = $mc->officialName;
                        $contractor->organization_id = $organization ? $organization->id : null;
                        $contractor->is_extra = $countContractors > 0;
                        $contractor->save();

                        $countContractors++;
                    }
                }
            }

            // AWARDED PRIZE winners (=contractors)~
            if ($data->results && $data->results->winners) {
                foreach($data->results->winners as $w) {
                    $organization = $this->matchOrCreateOrganization($w->nationalId, $w->officialName);

                    // restrict the saved contractors so that
                    // the same organization is only used once per dataset
                    if ($organization && !in_array($organization->id,$organizations)) {
                        $organizations[] = $organization->id;

                        $contractor = new Contractor();
                        $contractor->dataset_id = $dataset->id;
                        $contractor->national_id = $w->nationalId;
                        $contractor->name = $w->officialName;
                        $contractor->organization_id = $organization ? $organization->id : null;
                        $contractor->is_extra = $countContractors > 0;
                        $contractor->save();

                        $countContractors++;
                    }
                }
            }

            // UPDATE VERSION INFO FOR OLDER "SIBLINGS"
            /*
            $kerndatenSiblings = ScraperKerndaten::where('quelle',$record->quelle)
                ->where('item_id',$record->item_id)
                ->where('id','<>',$record->id)
                ->pluck('id');

            $currentDataset = Dataset::where('is_current_version',1)->whereIn('result_id',$kerndatenSiblings->toArray())->first();
            if ($currentDataset) {
                $currentDataset->is_current_version = 0;
                $currentDataset->save();
            }
            */
            $currentDataset = Dataset::withoutGlobalScope('not_disabled')->where('metaset_id',$dataset->metaset_id)
                ->where('is_current_version',1)
                ->where('id','<>',$dataset->id)
                ->first();
            if ($currentDataset) {
                $currentDataset->is_current_version = 0;
                $currentDataset->save();
            }

            // FINALLY, WRITE BACK INTO KERNDATEN RECORD
            $record->app_processed_at = $this->timestamp;
            $record->app_dataset_id = $dataset->id;
            $record->save();

            DB::commit();

            return true;

        } catch(\Exception $ex) {
            $this->log->error('Unable to write dataset for record:'.$record->id.' to database.');
            $this->log->error($ex->getCode() . ' ' . $ex->getMessage(),['trace' => $ex->getTraceAsString(),'data' => $data, 'record_id' => $record->id ]);

            dump('ERROR: Unable to write dataset for record:'.$record->id.' to database.');

            DB::rollBack();
        }

        return false;
    }

    /**
     * @param $id
     * @param $name
     *
     * @return null|\App\Organization
     */
    protected function matchOrCreateOrganization($id, $name) {
        $parser = null;
        if ($id !== null) {
            $parser = new NationalIdParser($id);
            $parser->parse();
        }

        // nice?
        if ($parser && $parser->isValid() && !$parser->isUnknown()) {
            $formatted = $parser->getFormattedId();
            $type = strtolower($parser->getType());     // transform to lower case so it can be used in where clause

            $existing = Organization::where($type,$formatted)->first();

            if ($existing) {
                return $existing;
            } else {
                // new up!
                return Organization::createFromType($formatted,$type,$name);
            }
        }

        // not so nice
        if ($parser && $parser->isValid() && $parser->isUnknown()) {
            $existing = Organization::where('ukn',$id)->first();

            if ($existing) {
                return $existing;
            } else {
                // new up!
                return Organization::createFromUnknownType($id, $name);
            }
        }

        // opposite of nice
        $existing = Organization::where('name',$name)->get();  // lucky match?
        if ($existing->count() === 1) {
            $existing = $existing->first();
        } else if ($existing->count() > 1) {
            // yikes, multiple organizations with the same name in db
            // try to find the super generic version
            $existing = Organization::where('name',$name)
                ->whereNull('fn')
                ->whereNull('gln')
                ->whereNull('gkz')
                ->whereNull('ukn')
                ->where('is_identified',0)
                ->get();

            if ($existing->count() === 1) {
                $existing = $existing->first();
            } else {
                $existing = null;
            }
        } else {
            $existing = null;
        }

        if ($existing) {
            return $existing;
        } else {
            // new organization!
            return Organization::createGeneric($name);
        }
    }

    /**
     * @param $record
     * @param $data
     * @return bool
     */
    protected function validate($record, $data) {

        // Validate foreign key values before attempting to write
        $validationError = false;

        $checkCPVCode  = $data->objectContract->cpv  ? CPV::find($data->objectContract->cpv) : true;
        if (!$checkCPVCode) {
            $this->dumpAndLogValidationWarning($record, 'Unknown CPV #'.$data->objectContract->cpv.'#. Code removed and set to NULL.');
            $data->objectContract->cpv = null;
        }

        $checkNUTSCode = $data->objectContract->nuts ? NUTS::find($data->objectContract->nuts) : true;
        if (!$checkNUTSCode) {
            $this->dumpAndLogValidationWarning($record, 'Unknown NUTS #'.$data->objectContract->nuts.'#. Code removed and set to NULL.');
            $data->objectContract->nuts = null;
        }

        if ($data->contractingBody->urlDocumentIsRestricted && $data->contractingBody->urlDocumentIsFull) {
            $this->dumpAndLogValidationError($record, 'Both Document Flags (RESTRICTED&FULL) set instead of one or none');
            $validationError = true;
        }

        if ($data->objectContract->lot && $data->objectContract->noLot) {
            $this->dumpAndLogValidationError($record, 'Both LOT Flags (LOT_DIVISION&NO_LOT_DIVISION) set instead of one or none');
            $validationError = true;
        }

        if ($data->additionalCoreData && $data->additionalCoreData->belowThreshold
            && $data->additionalCoreData->aboveThreshold) {
            $this->dumpAndLogValidationError($record, 'Both THRESHOLD Flags (ABOVE&BELOW) set instead of one or none');
            $validationError = true;
        }

        // Check additional cpvs if they don't exist don't throw an error but remove those that dont exist
        if (count($data->objectContract->additionalCpvs) > 0) {
            $uniqueAdditionalCpvs = array_unique($data->objectContract->additionalCpvs);
            $checkAddCpvs = CPV::whereIn('code',$data->objectContract->additionalCpvs)->get()->pluck('code')->all();

            if (count($uniqueAdditionalCpvs) != count($checkAddCpvs)) {
                $this->dumpAndLogValidationWarning($record, 'At least one unknown additional cpv code. Wrong code(s) removed.',['additional_cpvs' => $data->objectContract->additionalCpvs]);
                $errorCpvs = array_diff($uniqueAdditionalCpvs, $checkAddCpvs);
                $okCpvs = array_diff($uniqueAdditionalCpvs, $errorCpvs);
                $data->objectContract->additionalCpvs = $okCpvs;
            }
        } else if($data->modificationsContract && count($data->modificationsContract->additionalCpvs) > 0) {
            $uniqueAdditionalCpvs = array_unique($data->modificationsContract->additionalCpvs);
            $checkAddCpvs = CPV::whereIn('code',$data->modificationsContract->additionalCpvs)->get()->pluck('code')->all();

            if (count($uniqueAdditionalCpvs) != count($checkAddCpvs)) {
                $this->dumpAndLogValidationWarning($record, 'At least one unknown additional cpv code (modifications contract). Wrong code removed',['additional_cpvs' => $data->modificationsContract->additionalCpvs ]);
                $errorCpvs = array_diff($uniqueAdditionalCpvs, $checkAddCpvs);
                $okCpvs = array_diff($uniqueAdditionalCpvs, $errorCpvs);
                $data->modificationsContract->additionalCpvs = $okCpvs;
            }
        }

        if ($data->additionalCoreData && $data->additionalCoreData->nbSmeContractor
            && $data->awardContract && $data->awardContract->nbSmeContractor) {
            $this->dumpAndLogValidationError($record, 'Both NB_SME_CONTRACTOR attributes are set (additional core data & award contract)!!');
            $validationError = true;
        }
	
	if ($data->modificationsContract && $data->modificationsContract->valTotalAfter
	    && $data->modificationsContract->valTotalAfter<0) {
            $this->dumpAndLogValidationError($record, 'ModificationisContract->valTotalAfter < 0');
            $validationError = true;
        }

        if ($data->modificationsContract && $data->modificationsContract->cpv && $data->objectContract->cpv
            && $data->modificationsContract->cpv != $data->objectContract->cpv) {
            $this->dumpAndLogValidationError($record, 'Differing CPV Codes in OBJECT_CONTRACT and MODIFICATIONS_CONTRACT !!');
            $validationError = true;
        }

        if ($data->modificationsContract && $data->modificationsContract->additionalNeed
            && $data->modificationsContract->unforeseenCircumstance) {
            $this->dumpAndLogValidationError($record, 'Both INFO_MODIFICATIONS text fields are set (additional_need & unforeseen_circumstance) !!');
            $validationError = true;
        }

        return $validationError ? false : true;
    }

    /**
     * @param $record
     * @param $message
     * @param string $logLevel (info,warning,error etc.), @see Monolog documentation
     * @param $context
     * @param $logLevel
     */
    protected function dumpAndLogValidationError($record, $message, $context = [], $logLevel = 'error') {
        $context['message'] = $message;

        dump('Failed validation for kerndaten record id='.$record->id);
        dump($context);

        $this->log->{$logLevel}('Failed validation for kerndaten record id='.$record->id,$context);
    }

    /**
     * @param $record
     * @param $message
     * @param string $logLevel (info,warning,error etc.), @see Monolog documentation
     * @param $context
     * @param $logLevel
     */
    protected function dumpAndLogValidationWarning($record, $message, $context = [], $logLevel = 'warning') {
        $context['message'] = $message;

        dump('Validation warning for kerndaten record id='.$record->id);
        dump($context);

        $this->log->{$logLevel}('Validation warning for kerndaten record id='.$record->id,$context);
    }
}

<?php

namespace App\Console\Commands;

use App\Contractor;
use App\Dataset;
use App\DataSourcePreProcessor;
use App\NationalIdParser;
use App\Organization;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReProcessContractors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:re-process-contractors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performs a complete (meaining: FOR ALL Datasets) re-processing of contractors.';

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
        $start = Carbon::now();
        $this->info('Starting Re-Process-Contractors Job');
        Log::channel('processor_daily')->info('Starting Re-Process-Contractors Job');

        // reset contractors table
        $this->resetContractors();

        // load all IDs at once
        $datasetIds = $this->fetchDatasetIds();

        $counter = 0;

        // loop all IDs, but load only one Dataset Model at a time
        foreach($datasetIds as $id) {
            $counter++;
            $dataset = Dataset::find($id);

            try {
                $this->reProcessContractor($dataset);
            } catch(\Exception $ex) {
                $this->error($ex->getTraceAsString());
                $this->error('Ran into exception on dataset '.$dataset->id);
            }

            if ($counter%200 === 0) {
                $this->info('Re-processed contractors for '.$counter. ' datasets.');
            }
        }


        $runtime = $start->diffInSeconds(Carbon::now());
        $this->info('Finished Re-Process-Contractors Job in '.$runtime.' seconds');
        Log::channel('processor_daily')->info('Finished Re-Process-Contractors Job in '.$runtime.' seconds');
    }

    protected function fetchDatasetIds() {
        $result = DB::table('datasets')->select('id')->pluck('id');

        return $result;
    }

    protected function resetContractors() {
        DB::table('contractors')->delete();
    }

    protected function reProcessContractor(Dataset $dataset) {
        $preProcessor = new DataSourcePreProcessor();
        $preProcessor->preProcess($dataset->xml);

        // following code more or less duplicate from App\Jobs\Process
        $data = $preProcessor->getData();

        $organizations = [];

        // Handle contractors
        // AWARD contractors
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
                    $contractor->save();
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
                    $contractor->save();
                }
            }
        }
        // AWARDED PRIZE winners (=contractors)~
        if ($data->awardedPrize && $data->awardedPrize->winners) {
            foreach($data->awardedPrize->winners as $w) {
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
                    $contractor->save();
                }
            }
        }
    }

    /**
     * DUPLICATE OF App\Jobs\Process::matchOrCreateOrganization
     * (copied at 2019-10-12)
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
}

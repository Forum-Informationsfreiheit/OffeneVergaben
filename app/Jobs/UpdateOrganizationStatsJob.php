<?php

namespace App\Jobs;

use App\Offeror;
use App\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrganizationStatsJob implements ShouldQueue
{
    use Dispatchable;

    protected $organization_id = null;

    /**
     * Create a new job instance.
     *
     * @param null $organization_id optional organization_id to run update process for a single organization only
     *
     * @return void
     */
    public function __construct($organization_id = null)
    {
        $this->organization_id = $organization_id;
    }

    /**
     * Execute the job.
     *
     * Updates these attributes on each (or only a single) organization:
     * - count_ausschreibung_offeror
     * - count_ausschreibung_contractor
     * - count_auftrag_offeror
     * - count_auftrag_contractor
     * - val_total_auftrag_offeror
     * - val_total_auftrag_contractor
     *
     * @return void
     */
    public function handle() {
        Log::debug("Organization stats Job started", [ 'parameters' => [ 'organization_id' => $this->organization_id ]] );

        // check before proceeding
        if ($this->organization_id && !Organization::find($this->organization_id)) {
            Log::error("Unknown organization id ".$this->organization_id.". Exit.");
            return;
        }

        $blockSize = 250;
        $index = 0;

        $organizations = $this->getOrganizations($index, $blockSize);

        while($organizations && count($organizations) > 0) {

            $ids = $organizations->pluck('id');

            // calculate values, get value maps keyed by organization id

            // ausschreibungen as offeror
            $valuesOfferors0 = $this->makeValuesQuery("offerors",0,$ids)->get()->keyBy('id');

            // aufträge as offeror
            $valuesOfferors1 = $this->makeValuesQuery("offerors",1,$ids)->get()->keyBy('id');

            // ausschreibungen as contractor
            $valuesContractors0 = $this->makeValuesQuery("contractors",0,$ids)->get()->keyBy('id');

            // aufträge as contractor
            $valuesContractors1 = $this->makeValuesQuery("contractors",1,$ids)->get()->keyBy('id');

//            dump($valuesOfferors0);
//            dump($valuesOfferors1);
//            dump($valuesContractors0);
//            dump($valuesContractors1);

            // update
            foreach($organizations as $org) {

                // organization as offeror
                if ($valuesOfferors0->has($org->id)) {
                    $org->count_ausschreibung_offeror = $valuesOfferors0->get($org->id)->datasets_count;
                }
                if ($valuesOfferors1->has($org->id)) {
                    $org->count_auftrag_offeror = $valuesOfferors1->get($org->id)->datasets_count;

                    // value can be null because of missing/bad data
                    $val = $valuesOfferors1->get($org->id)->sum_val_total;
                    $org->val_total_auftrag_offeror = $val !== null ? $val : 0;
                }

                // organization as contractor
                if ($valuesContractors0->has($org->id)) {
                    $org->count_ausschreibung_contractor = $valuesContractors0->get($org->id)->datasets_count;
                }
                if ($valuesContractors1->has($org->id)) {
                    $org->count_auftrag_contractor = $valuesContractors1->get($org->id)->datasets_count;

                    $val = $valuesContractors1->get($org->id)->sum_val_total;
                    $org->val_total_auftrag_contractor = $val !== null ? $val : 0;
                }

                $org->save();
            }

            // prepare for next iteration
            $index += count($organizations);
            $organizations = $this->getOrganizations($index, $blockSize);

            Log::debug("Ran stats update for $index organizations");
        }

        Log::debug("Organization stats Job finished");
    }

    protected function getOrganizations($offset = 0, $blockSize = 100) {
        $query = Organization::query();

        if ($this->organization_id) {
            $query->where('id',$this->organization_id);
        }

        $query->offset($offset)->limit($blockSize);

        $records = $query->get();

        return $records->count() > 0 ? $records : null;
    }

    /**
     * @param $type = "offerors" or "contractors"
     * @param $end  = 0 (=Ausschreibung) or 1 (Auftrage)
     * @param $ids
     * @return \Illuminate\Database\Query\Builder|null
     */
    protected function makeValuesQuery($type, $end, $ids) {

        if (!in_array($type,['offerors','contractors'])) {
            return null;
        }
        if (!in_array($end,[0,1],true)) {
            return null;
        }

        $table = $type;

        $query = DB::table($table)
            ->select([$table.'.organization_id as id', DB::raw('count(*) as datasets_count'), DB::raw('sum(val_total) as sum_val_total') ])
            ->join('datasets',$table.'.dataset_id','=','datasets.id')
            ->join('dataset_types','datasets.type_code','=','dataset_types.code')
            ->where('dataset_types.end',$end)
            ->where('datasets.is_current_version',1)->where('datasets.disabled_at',null)
            ->whereIn($table.'.organization_id',$ids)
            ->groupBy($table.'.organization_id');

        return $query;
    }
}

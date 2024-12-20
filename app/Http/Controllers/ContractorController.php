<?php

namespace App\Http\Controllers;

use App\Contractor;
use App\CPV;
use App\Dataset;
use App\Http\Filters\ContractorFilter;
use App\Http\Filters\DatasetFilter;
use App\Http\Filters\OrganizationAsContractorFilter;
use App\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractorController extends Controller
{
    public function index(OrganizationAsContractorFilter $filters) {
        if_debug_mode_enable_query_log();
//        $totalItems = Organization::whereHas('contractors')->count();
//
//        $query = Contractor::indexQuery()->filter($filters);
//        $data  = $query->paginate(20);
//
//        $values = $data->keyBy('organization_id');
//
//        // now load the appropriate models for the view
//        $items = Organization::loadInOrder($data->pluck('organization_id')->toArray());
//
//        foreach($items as &$item) {
//            $item->datasets_count = $values[$item->id]->datasets_count;
//            $item->sum_val_total  = $values[$item->id]->sum_val_total;
//        }

        $query = Organization::query()
            ->filter($filters)
            ->select([
                'organizations.*',
                DB::raw('count_contractor as datasets_count'),
                DB::raw('val_total_auftrag_contractor as sum_val_total')
            ])
            ->where('count_contractor','>',0);

        $data  = $query->paginate(20);
        $items = $data->items();
        $totalItems = $data->total();

        return view('public.contractors.index',compact('items','totalItems','data','filters'));
    }

    public function show(DatasetFilter $filters, $id) {
        if_debug_mode_enable_query_log();
        $org = Organization::findOrFail($id);

        // re-use the main index query for datasets, but restricted to the
        // organization id of the contractor
        $query = Dataset::indexQuery(['allContractors' => true])
            ->filter($filters)
            ->where('contractors.organization_id',$org->id);

        if (!$filters->has('sort')) {
            // apply default sorting, item aktualisierungsdatum
            $query->orderBy('item_lastmod','desc');
        }

        $totalItems = $query->count();
        $data       = $query->paginate(20);     // data holds the pagination-aware builder

        if (count($data)) {
            $items = Dataset::loadInOrder($data->pluck('id')->toArray())
                ->withCount('offerors')
                ->get();
        } else {
            $items = [];
        }

        $stats      = $this->getContractorStats($org->id);

        return view('public.contractors.show',compact('items','totalItems','org','filters','data','stats'));
    }

    protected function getContractorStats($orgId) {

        $stats = new \stdClass();

        // 1. Anzahl gewonnene Aufträge
        $query = DB::table('datasets')
            ->join('contractors','contractors.dataset_id','=','datasets.id')
            ->where('contractors.organization_id',$orgId)
            ->where('datasets.disabled_at',null)
            ->where('datasets.is_current_version',1);
        $stats->totalCount = $query->count();

        // 2. Die durchschnittliche Anzahl der Bieter bei gewonnenn Aufträgen
        $stats->totalTenders = intval($query->sum('datasets.nb_tenders_received'));

        // 3. Die 5 häufigsten Kategorien
        $query = DB::table('datasets')
            ->select(['datasets.cpv_code',DB::raw('COUNT(*) as "cpv_count"')])
            ->join('contractors','contractors.dataset_id','=','datasets.id')
            ->where('datasets.cpv_code','<>',null)
            ->where('contractors.organization_id',$orgId)
            ->where('datasets.is_current_version',1)
            ->where('datasets.disabled_at',null)
            ->groupBy('datasets.cpv_code')
            ->orderBy('cpv_count','desc')
            ->limit(5);

        $topCpvs = $query->get();
        $cpvs = CPV::whereIn('code',$topCpvs->pluck('cpv_code')->toArray())->get()->keyBy('code');
        $stats->topCpvs = $topCpvs->map(function($item) use($cpvs) {
            $item->cpv = $cpvs->get($item->cpv_code);
            return $item;
        });

        // 4. Die 5 Top Auftraggeber (nach Anzahl konkreter Aufträge)
        $query = DB::table('datasets')
            ->select(['offerors.organization_id',DB::raw('COUNT(*) as "offeror_count"')])
            ->join('offerors','offerors.dataset_id','=','datasets.id')
            ->join('contractors','contractors.dataset_id','=','datasets.id')
            ->where('contractors.organization_id',$orgId)
            ->where('datasets.is_current_version',1)
            ->where('datasets.disabled_at',null)
            ->groupBy('offerors.organization_id')
            ->orderBy('offeror_count','desc')
            ->limit(5);

        $topOfferors = $query->get();
        $offerors = Organization::whereIn('id',$topOfferors->pluck('organization_id')->toArray())->get()->keyBy('id');
        $stats->topOfferors = $topOfferors->map(function($item) use($offerors) {
            $item->org = $offerors->get($item->organization_id);
            return $item;
        });

        return $stats;
    }
}

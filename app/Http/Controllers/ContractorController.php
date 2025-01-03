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

        $request = request();

        // for the base query join on contractors table is mandatory (fixed to this organization $id)
        // join on offerors table is optional (only needed if user wants to sort by offerors)
        if (($request->has('sort') && in_array($request->input('sort'),['offeror','-offeror']))) {
            $query = Dataset::indexQuery(['allContractors' => true]);
        } else {
            $query = Dataset::indexQuery(['joinOfferors' => false, 'allContractors' => true]);
        }

        $query->filter($filters)->where('contractors.organization_id',$org->id);

        if (!$filters->has('sort')) {
            // apply default sorting, item aktualisierungsdatum
            $query->orderBy('item_lastmod','desc');
        }

        $data       = $query->paginate(20);     // data holds the pagination-aware builder
        $totalItems = $data->total();

        if (count($data)) {
            $items = Dataset::loadInOrder($data->pluck('id')->toArray())
                ->withCount('offerors')
                ->with('offeror')    // pre loads the main offeror "is_extra === 0"
                ->with('offerors')
                ->with('contractor') // pre loads the main contractor "is_extra === 0"
                ->with('contractors')
                ->with('cpv')
                ->get();
        } else {
            $items = [];
        }

        $stats      = $this->getContractorStats($org);

        return view('public.contractors.show',compact('items','totalItems','org','filters','data','stats'));
    }

    protected function getContractorStats($organization) {

        $stats = new \stdClass();

        // Query in parts calculates values that are pre-calculated now:
        // datasets_count (== organization->count_auftrag_contractor) and
        // but nb_tenders_received is not so query is required anyway. can be easily modified though
        $query = DB::table('datasets')
            ->select([
                DB::raw('count(*) as datasets_count'),
                DB::raw('sum(datasets.nb_tenders_received) as sum_nb_tenders_received'),
            ])
            ->join('contractors','contractors.dataset_id','=','datasets.id')
            ->where('contractors.organization_id',$organization->id)
            ->where('datasets.disabled_at',null)
            ->where('datasets.is_current_version',1);

        $statsRecord = $query->first();

        // 1. Anzahl gewonnene Aufträge
        $stats->totalCount = $statsRecord->datasets_count;

        // 2. Die durchschnittliche Anzahl der Bieter bei gewonnenn Aufträgen
        $stats->totalTenders = intval($statsRecord->sum_nb_tenders_received);

        // 3. Die 5 häufigsten Kategorien
        $query = DB::table('datasets')
            ->select(['datasets.cpv_code',DB::raw('COUNT(*) as "cpv_count"')])
            ->join('contractors','contractors.dataset_id','=','datasets.id')
            ->where('datasets.cpv_code','<>',null)
            ->where('contractors.organization_id',$organization->id)
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
            ->where('contractors.organization_id',$organization->id)
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

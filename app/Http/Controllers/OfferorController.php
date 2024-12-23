<?php

namespace App\Http\Controllers;

use App\CPV;
use App\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Http\Filters\OfferorFilter;
use App\Http\Filters\OrganizationAsOfferorFilter;
use App\Offeror;
use App\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OfferorController extends Controller
{
    public function index(OrganizationAsOfferorFilter $filters) {
        if_debug_mode_enable_query_log();
//        $totalItems = Organization::whereHas('offerors')->count();
//
//        $query = Offeror::indexQuery()->filter($filters);
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
                DB::raw('count_offeror as datasets_count'),
                DB::raw('val_total_auftrag_offeror as sum_val_total')
            ])
            ->where('count_offeror','>',0);

        $data  = $query->paginate(20);
        $items = $data->items();
        $totalItems = $data->total();

        return view('public.offerors.index',compact('items','totalItems','data','filters'));
    }

    public function show(DatasetFilter $filters, $id) {
        if_debug_mode_enable_query_log();
        $org = Organization::findOrFail($id);

        $request = request();

        // for the base query join on offerors table is mandatory (fixed to this organization $id)
        // join on contractors table is optional (only needed if user wants to sort by contractors)
        if (($request->has('sort') && in_array($request->input('sort'),['contractor','-contractor']))) {
            $query = Dataset::indexQuery(['allOfferors' => true]);
        } else {
            $query = Dataset::indexQuery(['joinContractors' => false, 'allOfferors' => true]);
        }

        $query->filter($filters)->where('offerors.organization_id',$org->id);

        if (!$filters->has('sort')) {
            // apply default sorting, item aktualisierungsdatum
            $query->orderBy('item_lastmod','desc');
        }

        $data       = $query->paginate(20);
        $totalItems = $data->total();

        // 2024-12-19 fix no data handling
        if (count($data)) {
            // now load the appropriate models for the view
            $items = Dataset::loadInOrder($data->pluck('id')->toArray())
                ->with('offeror')    // pre loads the main offeror "is_extra === 0"
                ->with('offerors')
                ->with('contractor') // pre loads the main contractor "is_extra === 0"
                ->with('contractors')
                ->with('cpv')
                ->withCount('contractors')
                ->get();
        } else {
            $items = [];
        }

        $stats = $this->getOfferorStats($org);

        return view('public.offerors.show',compact('items','totalItems','org','filters','data','stats'));
    }

    protected function getOfferorStats($organization) {

        $stats = new \stdClass();

        // Query in parts calculates values that are pre-calculated now:
        // datasets_count (== organization->count_auftrag_offeror) and
        // sum_val_total  (== organization->val_total_auftrag_offeror)
        // but nb_tenders_received is not so query is required anyway
        $query = DB::table('datasets')
            ->select([
                DB::raw('count(*) as datasets_count'),
                DB::raw('sum(datasets.nb_tenders_received) as sum_nb_tenders_received'),
                DB::raw('sum(datasets.val_total) as sum_val_total'),
            ])
            ->join('offerors','offerors.dataset_id','=','datasets.id')
            ->join('dataset_types','datasets.type_code','=','dataset_types.code')
            ->where('dataset_types.end',1)
            ->where('offerors.organization_id',$organization->id)
            ->where('datasets.is_current_version',1)
            ->where('datasets.disabled_at',null);

        $statsRecord = $query->first();

        // 1. Anzahl vergebene Aufträge (Achtung: NUR Aufträge nicht Ausschreibungen!)
        $stats->totalCount = $statsRecord->datasets_count;

        // 2. Die durchschnittliche Anzahl der Bieter bei gewonnenen Aufträgen
        $stats->totalTenders = intval($statsRecord->sum_nb_tenders_received);

        // 3. Gesamtvolumen
        $stats->totalVal = intval($statsRecord->sum_val_total);

        // 3. Die 5 häufigsten Kategorien
        $query = DB::table('datasets')
            ->select(['datasets.cpv_code',DB::raw('COUNT(*) as "cpv_count"')])
            ->join('offerors','offerors.dataset_id','=','datasets.id')
            ->where('offerors.organization_id',$organization->id)
            ->where('datasets.is_current_version',1)
            ->where('datasets.cpv_code','<>',null)
            ->groupBy('datasets.cpv_code')
            ->orderBy('cpv_count','desc')
            ->limit(5);

        $topCpvs = $query->get();
        $cpvs = CPV::whereIn('code',$topCpvs->pluck('cpv_code')->toArray())->get()->keyBy('code');
        $stats->topCpvs = $topCpvs->map(function($item) use($cpvs) {
            $item->cpv = $cpvs->get($item->cpv_code);
            return $item;
        });

        // 4. Die 5 Top Lieferanten (nach Anzahl konkreter Aufträge)
        $query = DB::table('datasets')
            ->select(['contractors.organization_id',DB::raw('COUNT(*) as "contractor_count"')])
            ->join('contractors','contractors.dataset_id','=','datasets.id')
            ->join('offerors','offerors.dataset_id','=','datasets.id')
            ->where('offerors.organization_id',$organization->id)
            ->where('datasets.is_current_version',1)
            ->groupBy('contractors.organization_id')
            ->orderBy('contractor_count','desc')
            ->limit(5);

        $topContractors = $query->get();
        $contractors = Organization::whereIn('id',$topContractors->pluck('organization_id')->toArray())->get()->keyBy('id');
        $stats->topContractors = $topContractors->map(function($item) use($contractors) {
            $item->org = $contractors->get($item->organization_id);
            return $item;
        });

        return $stats;
    }
}

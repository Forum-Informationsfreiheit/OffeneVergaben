<?php

namespace App\Http\Controllers;

use App\CPV;
use App\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Http\Filters\OfferorFilter;
use App\Offeror;
use App\Organization;
use Illuminate\Support\Facades\DB;

class OfferorController extends Controller
{
    public function index(OfferorFilter $filters) {
        $totalItems = Organization::whereHas('offerors')->count();

        $query = Offeror::indexQuery()->filter($filters);
        $data  = $query->paginate(20);

        $values = $data->keyBy('organization_id');

        // now load the appropriate models for the view
        $items = Organization::loadInOrder($data->pluck('organization_id')->toArray());

        foreach($items as &$item) {
            $item->datasets_count = $values[$item->id]->datasets_count;
            $item->sum_val_total  = $values[$item->id]->sum_val_total;
        }

        return view('public.offerors.index',compact('items','totalItems','data','filters'));
    }

    public function show(DatasetFilter $filters, $id) {
        $org = Organization::findOrFail($id);

        $query = Dataset::indexQuery(['allOfferors' => true])
            ->filter($filters)
            ->where('offerors.organization_id',$org->id);

        if (!$filters->has('sort')) {
            // apply default sorting, item aktualisierungsdatum
            $query->orderBy('item_lastmod','desc');
        }

        $totalItems = $query->count();
        $data       = $query->paginate(20);

        // now load the appropriate models for the view
        $items = Dataset::loadInOrder($data->pluck('id')->toArray())
            ->withCount('contractors')
            ->get();

        $stats = $this->getOfferorStats($id);

        return view('public.offerors.show',compact('items','totalItems','org','filters','data','stats'));
    }

    protected function getOfferorStats($orgId) {

        $stats = new \stdClass();

        // 1. Anzahl vergebene Aufträge (Achtung: NUR Aufträge nicht Ausschreibungen!)
        $query = DB::table('datasets')
            ->join('offerors','offerors.dataset_id','=','datasets.id')
            ->join('dataset_types','datasets.type_code','=','dataset_types.code')
            ->where('dataset_types.end',1)
            ->where('offerors.organization_id',$orgId)
            ->where('datasets.is_current_version',1)
            ->where('datasets.disabled_at',null);
        $stats->totalCount = $query->count();

        // 2. Die durchschnittliche Anzahl der Bieter bei gewonnenn Aufträgen
        $stats->totalTenders = intval($query->sum('datasets.nb_tenders_received'));

        // 3. Gesamtvolumen
        $stats->totalVal = intval($query->sum('datasets.val_total'));

        // 3. Die 5 häufigsten Kategorien
        $query = DB::table('datasets')
            ->select(['datasets.cpv_code',DB::raw('COUNT(*) as "cpv_count"')])
            ->join('offerors','offerors.dataset_id','=','datasets.id')
            ->where('offerors.organization_id',$orgId)
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
            ->where('offerors.organization_id',$orgId)
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

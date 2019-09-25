<?php

namespace App\Http\Controllers;

use App\Contractor;
use App\Dataset;
use App\Http\Filters\ContractorFilter;
use App\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractorController extends Controller
{
    public function index(ContractorFilter $filters) {

        $query = Contractor::indexQuery()->filter($filters);
        //$query = Contractor::indexQuery();

        $totalItems = Organization::whereHas('contractors')->count();
        $data       = $query->paginate(20);

        $orderedIds = $data->pluck('organization_id')->toArray();
        $orderedIdsStr = join(',',$orderedIds);

        $values = $data->keyBy('organization_id');

        // now load the appropriate models for the view
        $items = Organization::whereIn('id',$orderedIds)
            ->orderByRaw(DB::raw("FIELD(id, $orderedIdsStr)")) // https://stackoverflow.com/a/26704767/718980
            ->get();

        foreach($items as &$item) {
            $item->datasets_count = $values[$item->id]->datasets_count;
            $item->sum_val_total  = $values[$item->id]->sum_val_total;
        }

        return view('public.contractors.index',compact('items','totalItems','data','filters'));
    }

    public function show($id) {
        $org = Organization::findOrFail($id);
        $query = Dataset::select([
            'datasets.*',
            'res.created_at as scraped_at'
        ]);
        $query->join('scraper_results as res','datasets.result_id','=','res.id');
        $query->join('contractors','datasets.id','=','contractors.dataset_id');
        $query->where('contractors.organization_id',$org->id);
        $query->where('datasets.is_current_version',1);

        $items = $query->paginate(20);

        return view('public.contractors.show',compact('items','totalItems','org'));
    }
}

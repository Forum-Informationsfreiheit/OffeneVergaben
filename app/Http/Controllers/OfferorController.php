<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Http\Filters\OfferorFilter;
use App\Offeror;
use App\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferorController extends Controller
{
    public function index(OfferorFilter $filters) {
        $query = Offeror::indexQuery()->filter($filters);
        //$query = Contractor::indexQuery();

        $totalItems = Organization::whereHas('offerors')->count();
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

        return view('public.offerors.index',compact('items','totalItems','data','filters'));
    }

    public function indexOld() {
        $totalItems = Organization::with('offerors')->has('offerors')->count();

        $query = Organization::with('offerors')->has('offerors');

        $items = $query->paginate(20);

        return view('public.offerors.index',compact('items','totalItems'));
    }

    public function show(DatasetFilter $filters, $id) {
        /*
        $org = Organization::findOrFail($id);
        $query = Dataset::select([
            'datasets.*',
            'res.created_at as scraped_at'
        ]);
        $query->join('scraper_results as res','datasets.result_id','=','res.id');
        $query->join('offerors','datasets.id','=','offerors.dataset_id');
        $query->where('offerors.organization_id',$org->id);
        $query->where('datasets.is_current_version',1);

                $items = $query->paginate(20);
        */
        $org = Organization::findOrFail($id);

        $query = Dataset::indexQuery()
            ->filter($filters)
            ->where('offerors.organization_id',$org->id);

        $totalItems = $query->count();
        $data       = $query->paginate(20);

        // debug: this is the original sort order of the ids
        // dump($data->pluck('id')->toArray());

        $orderedIds = $data->pluck('id')->toArray();
        $orderedIdsStr = join(',',$orderedIds);

        // now load the appropriate models for the view
        $items = Dataset::whereIn('id',$orderedIds)
            ->orderByRaw(DB::raw("FIELD(id, $orderedIdsStr)")) // https://stackoverflow.com/a/26704767/718980
            ->get();

        return view('public.offerors.show',compact('items','totalItems','org','filters','data'));
    }
}

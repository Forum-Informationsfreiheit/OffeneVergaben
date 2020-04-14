<?php

namespace App\Http\Controllers;

use App\Contractor;
use App\Dataset;
use App\Http\Filters\ContractorFilter;
use App\Http\Filters\DatasetFilter;
use App\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractorController extends Controller
{
    public function index(ContractorFilter $filters) {
        $totalItems = Organization::whereHas('contractors')->count();

        $query = Contractor::indexQuery()->filter($filters);
        $data  = $query->paginate(20);

        $values = $data->keyBy('organization_id');

        // now load the appropriate models for the view
        $items = Organization::loadInOrder($data->pluck('organization_id')->toArray());

        foreach($items as &$item) {
            $item->datasets_count = $values[$item->id]->datasets_count;
            $item->sum_val_total  = $values[$item->id]->sum_val_total;
        }

        return view('public.contractors.index',compact('items','totalItems','data','filters'));
    }

    public function show(DatasetFilter $filters, $id) {
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

        return view('public.contractors.show',compact('items','totalItems','org','filters','data'));
    }
}

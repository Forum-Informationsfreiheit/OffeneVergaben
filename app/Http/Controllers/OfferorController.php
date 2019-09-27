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

        $query = Dataset::indexQuery()
            ->filter($filters)
            ->where('offerors.organization_id',$org->id);

        $totalItems = $query->count();
        $data       = $query->paginate(20);

        // now load the appropriate models for the view
        $items = Dataset::loadInOrder($data->pluck('id')->toArray());

        return view('public.offerors.show',compact('items','totalItems','org','filters','data'));
    }
}

<?php

namespace App\Http\Controllers;

use App\CPV;
use App\Dataset;
use App\Organization;
use Illuminate\Http\Request;

class DatasetController extends Controller
{
    public function index(Request $request) {
        $order = $request->has('orderBy') ? $request->input('orderBy') : 'offerors.name';
        $direction = $request->has('desc') ? 'desc' : 'asc';
        $cpvFilter = $request->has('cpvFilter') ? $request->input('cpvFilter') : null;
        $offerorFilter = $request->has('offerorFilter') ? $request->input('offerorFilter') : null;
        $contractorFilter = $request->has('contractorFilter') ? $request->input('contractorFilter') : null;

        $cpv = null;

        $query = Dataset::select([
            'datasets.*',
            'offerors.name as offeror_name',
            'offerors.national_id as offeror_national_id',
            'res.created_at as scraped_at'
        ]);
        $query->join('offerors', 'datasets.id', '=', 'offerors.dataset_id');
        //$query->where('offerors.is_extra',0);
        $query->join('scraper_results as res','datasets.result_id','=','res.id');

        if ($cpvFilter) {
            // quick check cpv
            $cpv = CPV::findOrFail($cpvFilter);

            $query->join('cpv_dataset as cd','datasets.id','=','cd.dataset_id');
            $query->where('cd.cpv_code','=',$cpvFilter);
        }

        if ($offerorFilter) {
            // quick check org
            $org = Organization::findOrFail($offerorFilter);

            $query->where('offerors.organization_id',$org->id);
        }

        if ($contractorFilter) {
            // quick check org
            $org = Organization::findOrFail($contractorFilter);

            $query->join('contractors','datasets.id','=','contractors.dataset_id');
            $query->where('contractors.organization_id',$org->id);
        }

        $query->orderBy($order,$direction);

        $showAll = $request->has('showAll');
        if ($showAll) {
            $items = $query->get();
        } else {
            $items = $query->paginate(20);
        }

        $paramsString = "orderBy=$order" . ($direction == "desc" ? "&desc=1" : "");

        return view('public.datasets.index',compact('items','showAll','paramsString','cpv','cpvFilter'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Organization;
use Illuminate\Http\Request;

class ContractorController extends Controller
{
    public function index() {
        $totalItems = Organization::with('contractors')->has('contractors')->count();

        $query = Organization::with('contractors')->has('contractors');

        $items = $query->paginate(20);

        return view('public.contractors.index',compact('items','totalItems'));
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

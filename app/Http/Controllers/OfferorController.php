<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Organization;
use Illuminate\Http\Request;

class OfferorController extends Controller
{
    public function index() {
        $totalItems = Organization::with('offerors')->has('offerors')->count();

        $query = Organization::with('offerors')->has('offerors');

        $items = $query->paginate(20);

        return view('public.offerors.index',compact('items','totalItems'));
    }

    public function show($id) {
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

        return view('public.offerors.show',compact('items','totalItems','org'));
    }
}

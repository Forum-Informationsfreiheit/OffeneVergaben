<?php

namespace App\Http\Controllers;

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
}

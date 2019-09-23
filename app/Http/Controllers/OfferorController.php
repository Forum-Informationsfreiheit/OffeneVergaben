<?php

namespace App\Http\Controllers;

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
}

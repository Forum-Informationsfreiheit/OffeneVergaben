<?php

namespace App\Http\Controllers\Admin;

use App\Dataset;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

class DatasetController extends Controller
{
    public function index(Request $request) {

        $query = Dataset::query();

        if ($request->has('id') && is_numeric($request->input('id'))) {
            $query->where('id',$request->input('id'));
            $query->withoutGlobalScope('not_disabled');
        }
        if ($request->has('inactive')) {
            $query->where('disabled_at','<>',null);
            $query->withoutGlobalScope('not_disabled');
        }

        $query->orderBy('item_lastmod','desc');

        $total = $query->count();

        $datasets = $query->paginate(50);

        return view('admin.datasets.index', compact('datasets','total'));
    }

    public function disable(Request $request) {
        $this->authorize('update-datasets');

        $dataset = Dataset::withoutGlobalScope('not_disabled')->findOrFail($request->input('id'));
        $allVersions = $dataset->metaset->datasets()->withoutGlobalScope('not_disabled')->get();

        foreach($allVersions as $datasetVersion) {
            $datasetVersion->disabled_at = $request->input('mode') === 'disable' ? Carbon::now() : null;
            $datasetVersion->save();
        }

        Flash::info($request->input('mode') === 'disable' ? 'Datensatz wurde deaktiviert.' : 'Deaktivierung des Datensatzes wurde zurückgenommen.');

        return redirect(route('admin::datasets',[ 'id' => $request->input('id') ]));
    }
}

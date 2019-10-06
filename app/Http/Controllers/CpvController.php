<?php

namespace App\Http\Controllers;

use App\CPV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laracasts\Utilities\JavaScript\JavaScriptFacade;

class CpvController extends Controller
{
    public function index(Request $request) {

        $totalItems = 0;

        //$items = CPV::paginate(100);

        $root = null;
        if ($request->has('node')) {
            $root = CPV::findOrFail($request->input('node'));
        }

        //$code = "33000000";
        //$root = CPV::where('code',$code)->first();

        //dump($root->toArray());

        // query sum(...)
        $result = $this->sumQuery($root)->map(function($i) use($root) {
            // put in more info about each node, which helps later with the treemap processing
            $i->isRoot = 0;
            $i->sum = (int) $i->sum;

            // if there is a root node the result includes the "leveled"(?)
            // root node (with 1 extra 0 at the end)
            // but with that extra 0 it won't be accessible in the cpvMap
            // therefore cut it.
            if ($root) {
                $trimmedCpv = rtrim($i->cpv,'0');

                if ($i->cpv != $trimmedCpv) {
                    $i->cpv = $trimmedCpv;
                    $i->isRoot = 1;
                }
            }

            $i->isLeaf = strlen($i->cpv == CPV::STR_CODE_LENGTH) ? 1 : 0;

            return $i;
        });

        $cpvMap = CPV::whereIn('trimmed_code',
            $result->pluck('cpv')->map(function($i) {

                // dont trim below 2 chars
                $trimmed = rtrim($i,'0');

                return strlen($trimmed) === 1 ? $trimmed . '0' : $trimmed;
            } ))
            ->get()->keyBy('trimmed_code');

        $items = $result;

        // query count(...)
        //$result2 = $this->countQuery($root);
        //dump($result2);



        //$data = $items;

        JavaScriptFacade::put([
            'rootNode' => $root,
            'cpvMap' => $cpvMap,
            'cpvRecords' => $result,
        ]);

        return view('public.cpvs.index',compact('totalItems','items','cpvMap'));
    }

    protected function sumQuery($root) {
        $query = DB::table('datasets')
            ->select(DB::raw('sum(val_total) as sum'))
            ->where('datasets.is_current_version',1)
            ->where('datasets.cpv_code','<>',null)
            ->where('datasets.val_total','<>',null);

        if ($root == null) {
            $query->addSelect(DB::raw('LEFT(datasets.cpv_code,2) as cpv'));
            $query->groupBy(DB::raw('LEFT(datasets.cpv_code,2)'));
        } else {
            $query->addSelect(DB::raw('LEFT(datasets.cpv_code,'.($root->level + 1).') as cpv'));
            $query->groupBy(DB::raw('LEFT(datasets.cpv_code,'.($root->level + 1).')'));
            $query->where(DB::raw('LEFT(datasets.cpv_code,'.$root->level.')'),$root->trimmed_code);
        }

        $result = $query->get();

        return $result;
    }

    protected function countQuery($root) {
        $query = DB::table('datasets')
            ->select(DB::raw('count(*) as count'))
            ->where('datasets.is_current_version',1)
            ->where('datasets.cpv_code','<>',null);

        if ($root == null) {
            $query->addSelect(DB::raw('LEFT(datasets.cpv_code,2) as cpv'));
            $query->groupBy(DB::raw('LEFT(datasets.cpv_code,2)'));
        } else {
            $query->addSelect(DB::raw('LEFT(datasets.cpv_code,'.($root->level + 1).') as cpv'));
            $query->groupBy(DB::raw('LEFT(datasets.cpv_code,'.($root->level + 1).')'));
            $query->where(DB::raw('LEFT(datasets.cpv_code,'.$root->level.')'),$root->trimmed_code);
        }

        $result = $query->get();

        return $result;
    }
}

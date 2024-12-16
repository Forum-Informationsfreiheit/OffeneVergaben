<?php

namespace App\Http\Controllers;

use App\CPV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laracasts\Utilities\JavaScript\JavaScriptFacade;

class CpvController extends Controller
{
    public static function buildViewUrl($defaultParams, $changedParams) {
        $tempParams = new \stdClass();

        foreach($defaultParams as $key => $value) {
            $tempParams->{$key} = isset($changedParams[$key]) ? $changedParams[$key] : $value;
        }

        $routeParams = [];

        if ($tempParams->root) {
            $routeParams['node'] = $tempParams->root->code;
        }
        if ($tempParams->type == 'anzahl') {
            $routeParams[] = 'anzahl';
        }

        $url = route('public::branchen',$routeParams);

        return $url;
    }

    public function index() {
        if_debug_mode_enable_query_log();
        $params = $this->buildViewParams();

        $result = $this->query($params);

        // Iterate over result and add treestructure data is-root, is-leaf flags
        $result = $result->map(function($i) use($params) {
            // put in more info about each node, which helps later with the treemap processing
            $i->isRoot = 0;

            if ($params->type == 'volume') {
                $i->sum = (int) $i->sum;
            } else {
                $i->count = (int) $i->count;
            }

            // if there is a root node the result includes the "leveled"(?)
            // root node (with 1 extra 0 at the end)
            // but with that extra 0 it won't be accessible in the cpvMap
            // therefore cut it.
            if ($params->root) {
                $trimmedCpv = rtrim($i->cpv,'0');

                // but don't trim too far there is no cpv code that has a trimmed code of only one char
                // so a trimmed code of '6' is actually '60' in the database
                $trimmedCpv = strlen($trimmedCpv) == 1 ? $trimmedCpv . '0' : $trimmedCpv;

                if ($i->cpv != $trimmedCpv) {
                    $i->cpv = $trimmedCpv;
                    $i->isRoot = 1;
                }
            }

            $i->isLeaf = strlen($i->cpv == CPV::STR_CODE_LENGTH) ? 1 : 0;

            return $i;
        });

        // add key value dictionary for cpv lookup by trimmed_code (for usage in javascript)
        $cpvMap = $this->cpvMap($result);

        $items = $result;

        // 2020-04-14: Damit in der Liste die Zahlen für den Root Node (falls vorhanden)
        //             mit dem gleichen Schema funktionieren wie die untergeordneten Nodes
        //             Sprich Gesamtanzahl anzeigen und Gesamtvolumen von RootNode*
        //             Braucht es ein extra query
        $rootNodeTotals = $params->root ? $this->rootNodeQuery($params) : null;

        JavaScriptFacade::put([
            'parameters' => $params,
            'cpvMap' => $cpvMap,
            'cpvRecords' => $result,
        ]);

        return view('public.cpvs.index',compact('params','items','cpvMap','rootNodeTotals'));
    }

    public function ajaxSearch(Request $request) {
        if (!$request->ajax()) {
            abort(403);
        }

        $search = $request->input('query');
        $data = [];
        if (!$search) {
            return response()->json($data);
        }

        $search = rtrim($search,'*');

        if (is_numeric($search)) {
            $data = CPV::select(['code','trimmed_code','name'])->where('code','like',$search.'%')
                ->orderBy('code','asc')
                ->limit(100)
                ->get();
        } else {
            $data = CPV::select(['code','trimmed_code','name'])->where('name','like','%'.$search.'%')
                ->orderBy('code','asc')
                ->limit(100)
                ->get();
        }

        return response()->json($data);
    }

    protected function cpvMap($result) {
        $cpvMap = CPV::whereIn('trimmed_code',
            $result->pluck('cpv')->map(function($i) {

                // dont trim below 2 chars
                $trimmed = rtrim($i,'0');

                return strlen($trimmed) === 1 ? $trimmed . '0' : $trimmed;
            } ))
            ->get()->keyBy('trimmed_code');

        return $cpvMap;
    }

    protected function buildViewParams() {
        $params = new \stdClass();

        $params->type = 'volume';
        $params->year = null; // not yet needed / implemented
        $params->root = null;
        $params->baseUrl = route('public::branchen');

        if (request()->has('anzahl')) {
            $params->type = 'anzahl';
        }

        if (request()->has('node')) {
            $params->root = CPV::findOrFail(request()->input('node'));
        }

        return $params;
    }

    protected function query($params) {
        /*
        if ($params->type == 'volume') {
            return $this->volumeQuery($params->root);
        }
        if ($params->type == 'anzahl') {
            return $this->countQuery($params->root);
        }
        */

        $query = DB::table('datasets')
            ->select([DB::raw('sum(val_total) as sum'), DB::raw('count(*) as count')])
            ->where('datasets.is_current_version',1)->where('datasets.disabled_at',null)
            ->where('datasets.cpv_code','<>',null);
        //->where('datasets.val_total','<>',null);

        if ($params->root == null) {
            $query->addSelect(DB::raw('LEFT(datasets.cpv_code,2) as cpv'));
            $query->groupBy(DB::raw('LEFT(datasets.cpv_code,2)'));
        } else {
            $query->addSelect(DB::raw('LEFT(datasets.cpv_code,'.($params->root->level + 1).') as cpv'));
            $query->groupBy(DB::raw('LEFT(datasets.cpv_code,'.($params->root->level + 1).')'));
            $query->where(DB::raw('LEFT(datasets.cpv_code,'.$params->root->level.')'),$params->root->trimmed_code);
        }

        $result = $query->get();

        return $result;
    }

    protected function rootNodeQuery($params) {
        $query = DB::table('datasets')
            ->select([DB::raw('sum(val_total) as sum'), DB::raw('count(*) as count')])
            ->where('datasets.is_current_version',1)->where('datasets.disabled_at',null)
            ->where(DB::raw('LEFT(datasets.cpv_code,'.$params->root->level.')'),$params->root->trimmed_code)
            ->groupBy(DB::raw('LEFT(datasets.cpv_code,'.($params->root->level).')'));

        $result = $query->first();

        return $result;
    }

    /*
    protected function volumeQuery($root) {
        $query = DB::table('datasets')
            ->select(DB::raw('sum(val_total) as sum'))
            ->where('datasets.is_current_version',1)->where('datasets.disabled_at',null)
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
            ->where('datasets.is_current_version',1)->where('datasets.disabled_at',null)
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
    */
}

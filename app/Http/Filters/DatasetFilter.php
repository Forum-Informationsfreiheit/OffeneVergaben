<?php

namespace App\Http\Filters;

use App\Models\CPV;
use App\Models\DatasetType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatasetFilter extends QueryFilter
{
    public static $sortable = [
        'title',
        'offeror',
        'contractor',
        'cpv',
        'nb_tenders_received',
        'val_total',
        'item_lastmod'
    ];

    /**
     * @var array $appliedFilters
     *
     * Keep a key value store of the applied filters. Values have been validated.
     */
    protected $appliedFilters = [];

    // only single attribute sort implemented atm
    protected $sortedBy = null;
    protected $sortDirection = null;

    protected $datesMap = [
        'default' => 'item_lastmod',
        'dateSta' => 'date_start',

        // dateEnd kann von mehreren Inputs gefüttert werden:
        // 82_Z1: "Endzeitpunkt des dynamischen Beschaffungssystems"
        // 82_Z2: "Bei Zielschuldverhältnissen: in Aussicht genommener Erfüllungszeitpunkt", oder "Endzeitpunkt des dynamischen Beschaffungssystems"

        'dateEnd' => 'date_end',
        'dateRte' => 'datetime_receipt_tenders',
        'dateCCo' => 'date_conclusion_contract',
        'dateFPu' => 'date_first_publication',
        'dateLCh' => 'datetime_last_change',
        'dateDSt' => 'deadline_standstill'
    ];

    protected $nutsArray = [
        'NAT',
        // place of performance: Austria, without region (e.g. eForms "anywhere in the country")
        'AT',
        'AT11','AT12','AT13',
        'AT21','AT22',
        'AT31','AT32','AT33','AT34'
    ];

    /**
     * By implementing the sort(...) method it can automagically be called by the QueryFilter-Logic
     * on $query->filter(...) calls. Note that sort logic is entirely custom and not provided by the QueryFilter code.
     *
     * @param $field - the attribute the current query should be ordered by (single attribute only)
     *                 If prefixed by minus/dash ("-") character sorting will be executed in descending order
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function sort($field) {
        // unknown sort attribute? return early
        if ((substr($field, 0, 1) !== '-' && !in_array($field,self::$sortable)) ||
            (substr($field, 0, 1) === '-' && !in_array(substr($field,1),self::$sortable))) { // check prefix (desc sorting)

            // return default sort
            return $this->builder->orderBy('item_lastmod','desc');
        }

        $this->sortDirection = substr($field, 0, 1) == '-' ? 'desc' : 'asc';
        $this->sortedBy = substr($field, 0, 1) == '-' ? substr($field,1) : $field;

        // name_sort haelt die ersten 191 Zeichen von name und ist im Gegensatz zum
        // TEXT-Feld indiziert, sortiert wird daher ueber den Index statt per filesort
        if ($this->sortedBy == 'offeror') {
            return $this->builder->orderBy('offerors.name_sort',$this->sortDirection);
        }

        if ($this->sortedBy == 'contractor') {
            return $this->builder->orderBy('contractors.name_sort',$this->sortDirection);
        }

        if ($this->sortedBy == 'cpv') {
            return $this->builder->orderBy('datasets.cpv_code',$this->sortDirection);
        }

        return $this->builder->orderBy($this->sortedBy,$this->sortDirection);
    }

    public function types($values) {
        // query-filter 3.x hands the raw request value over, including '' and non-arrays
        // (1.x dropped empty values before calling). count() on those is a TypeError.
        if (!is_array($values)) {
            return $this->builder;
        }

        $count = count($values);

        if ($count == 0 || $count >= 2) {
            // multi checkbox
            // if 2 of 2 options are selected then that means everything is selected
            // --> no need for filtering, just take 'all', we achieve that by doing nothing
            return $this->builder;
        }

        $this->appliedFilters['types'] = $values;

        if ($values[0] == 'ausschreibung') {
            $codes = DatasetType::ausschreibung()->pluck('code')->toArray();
            return $this->builder->whereIn('type_code',$codes);
        }

        if ($values[0] == 'auftrag') {
            $codes = DatasetType::auftrag()->pluck('code')->toArray();
            return $this->builder->whereIn('type_code',$codes);
        }

        // ignore any other value
        return $this->builder;
    }

    public function contractTypes($values) {
        if (!$values || !is_array($values) || count($values) === 0) {
            return $this->builder;
        }

        $this->appliedFilters['contract_types'] = $values;

        $contractTypes = [];
        $contractTypesOcm = [];

        if (in_array('works',$values)) {
            $contractTypes[] = 'WORKS';
        }
        if (in_array('services',$values)) {
            $contractTypes[] = 'SERVICES';
        }
        if (in_array('supplies',$values)) {
            $contractTypes[] = 'SUPPLIES';
        }

        if (in_array('ocm_works',$values)) {
            $contractTypesOcm[] = 'WORKS';
        }
        if (in_array('ocm_services',$values)) {
            $contractTypesOcm[] = 'SERVICES';
        }
        if (in_array('ocm_supplies',$values)) {
            $contractTypesOcm[] = 'SUPPLIES';
        }

        if (count($contractTypes) > 0 || count($contractTypesOcm) > 0) {
            return $this->builder->where(function($query) use ($contractTypes, $contractTypesOcm) {
                return $query->whereIn('contract_type',$contractTypes)->orWhereIn('ocm_contract_type',$contractTypesOcm);
            });
        }

        // ignore any other value
        return $this->builder;
    }

    public function volumeFrom($value) {
        if (!is_numeric($value)) {
            return $this->builder;
        }

        $this->appliedFilters['volume_from'] = $value;

        return $this->builder->where('val_total','>=',$value * 100);
    }

    public function volumeTo($value) {
        if (!is_numeric($value)) {
            return $this->builder;
        }

        $this->appliedFilters['volume_to'] = $value;

        return $this->builder->where('val_total','<=',$value * 100);
    }

    public function tendersFrom($value) {
        if (!is_numeric($value)) {
            return $this->builder;
        }

        $this->appliedFilters['tenders_from'] = $value;

        return $this->builder->where('nb_tenders_received','>=',$value);
    }

    public function tendersTo($value) {
        if (!is_numeric($value)) {
            return $this->builder;
        }

        $this->appliedFilters['tenders_to'] = $value;

        return $this->builder->where('nb_tenders_received','<=',$value);
    }

    public function cpv($value) {
        if (!intval($value)) {
            return $this->builder;
        }

        $like = substr($value,-1) === "*";

        $this->appliedFilters['cpv'] = $value;

        // trim, but leave at least 2 characters
        $value = $like ? rtrim(rtrim($value,'*'),'0') : rtrim($value,'0');
        $value = strlen($value) == 1 ? $value . '0' : $value;

        if ($like || $this->request()->has('cpv_like')) {
            return $this->builder->where('cpv_code','like',$value.'%');
        } else {
            return $this->builder->where('cpv_code',CPV::zeroFill($value));
        }
    }

    /**
     * @param $value
     * @return $this|\Illuminate\Database\Eloquent\Builder
     */
    public function dateFrom($value) {
        // an empty date input reaches this method since query-filter 3.x, and
        // Carbon::parse('') is "now", which would silently filter everything away
        if ($value === null || $value === '') {
            return $this->builder;
        }

        if (!array_key_exists($this->request()->input('date_type'),$this->datesMap)) {
            return $this->builder;
        }

        try {
            // input is DATE only. so carbon datetime will be <yyyy-mm-dd> 00:00:00
            // time of 00:00:00 is perfect as it is the beginning of the day and we want
            // to include the whole day
            $dateFrom  = Carbon::parse($value);
            $dateField = $this->datesMap[$this->request()->input('date_type')];
            $this->appliedFilters['date_from'] = $value;

            return $this->builder->where($dateField,'>=',$dateFrom);

        } catch(\Exception $ex) {
            return $this->builder;
        }
    }

    /**
     * @param $value
     * @return $this|\Illuminate\Database\Eloquent\Builder
     */
    public function dateTo($value) {
        // see dateFrom()
        if ($value === null || $value === '') {
            return $this->builder;
        }

        if (!array_key_exists($this->request()->input('date_type'),$this->datesMap)) {
            return $this->builder;
        }

        try {
            // input is DATE only. so carbon datetime will be <yyyy-mm-dd> 00:00:00
            // to include the whole day we want to set the time to 23:59:59
            $dateTo  = Carbon::parse($value)->setHour(23)->setMinute(59)->setSecond(59);
            $dateField = $this->datesMap[$this->request()->input('date_type')];
            $this->appliedFilters['date_to'] = $value;

            return $this->builder->where($dateField,'<=',$dateTo);

        } catch(\Exception $ex) {
            return $this->builder;
        }
    }

    public function dateType($value) {
        // this param does nothing on its own
        // value of $this->request('date_param') will be used in dateFrom/dateTo methods
        // only remember in combination with date_from or date_to
        if ($this->request()->input('date_from') || $this->request()->input('date_to')) {
            $this->appliedFilters['date_type'] = $value;
        }
    }

    public function nuts($value) {
        if (!$value || !is_array($value) || count($value) === 0) {
            return $this->builder;
        }

        $this->appliedFilters['nuts'] = $value;

        $international = in_array('NAT',$value);

        // check the input against a whitelisted values, also remove NAT (not a real nuts code)
        $filtered = array_filter($value,function($elem) {
            return in_array($elem,$this->nutsArray) && $elem !== 'NAT';
        });

        // slightly complicated where clause ahead
        // as we need where grouping because of the special 'NOT' clause
        // build up the where clause, the international one makes it slightly complicated
        // also we have to use 'or where like x' clauses for each bundesland
        $where = $this->builder->where(function($query) use ($filtered, $international) {
            if (!$international) {
                return $query->whereIn(DB::raw('LEFT(nuts_code,4)'),$filtered);
            } else {
                if (count($filtered) === 0) {
                    return $query->where(DB::raw('LEFT(nuts_code,2)'),'<>','AT');
                } else {
                    return $query->whereIn(DB::raw('LEFT(nuts_code,4)'),$filtered)
                        ->orWhere(DB::raw('LEFT(nuts_code,2)'),'<>','AT');
                }
            }
        });

        return $where;
    }

    public function search($value) {
        if (!$value || strlen($value) <= 4) {
            // ignore silently
            return $this->builder;
        }

        $this->appliedFilters['search'] = $value;

        $term = $this->fulltextTerm($value);

        if ($term === '') {
            // nur Sonderzeichen eingegeben, nichts wonach sich suchen liesse
            return $this->builder;
        }

        // Frueher hing die Suche als OR ueber drei Tabellen im WHERE des Joins: MySQL musste
        // dafuer erst den kompletten Join bilden und dann jede Zeile einzeln pruefen, die
        // LIKE-Wildcards liessen ausserdem keinen Index zu (~1.8s allein fuer count(*)).
        // Stattdessen wird die Treffermenge vorab als UNION zusammengesetzt, jeder Zweig fuer
        // sich ueber einen Index, und erst dann auf datasets gejoint (~0.4s).
        $matches = DB::table('datasets')
            ->select('id as dataset_id')
            ->whereRaw('MATCH(title, description) AGAINST (? IN BOOLEAN MODE)',[$term])
            ->union(DB::table('offerors')->select('dataset_id')->where('is_extra',0)
                ->whereRaw('MATCH(name) AGAINST (? IN BOOLEAN MODE)',[$term]))
            ->union(DB::table('offerors')->select('dataset_id')->where('is_extra',0)
                ->where('national_id',$value))
            ->union(DB::table('offerors')->select('dataset_id')->where('is_extra',0)
                ->where('reference_number',$value))
            ->union(DB::table('contractors')->select('dataset_id')->where('is_extra',0)
                ->whereRaw('MATCH(name) AGAINST (? IN BOOLEAN MODE)',[$term]))
            ->union(DB::table('contractors')->select('dataset_id')->where('is_extra',0)
                ->where('national_id',$value));

        // UNION entfernt Duplikate, der Join vervielfacht die datasets-Zeilen also nicht
        return $this->builder->joinSub($matches,'search_match','search_match.dataset_id','=','datasets.id');
    }

    /**
     * Sucheingabe in einen Boolean-Mode-Ausdruck uebersetzen.
     *
     * Die Operatoren des Boolean Mode werden entfernt, sonst laesst sich mit einer Eingabe
     * wie "a -b" ein Syntaxfehler ausloesen. Jedes Wort wird als Pflichtwort mit
     * Prefix-Suche angehaengt ("wiener linien" -> "+wiener* +linien*").
     *
     * @param string $value
     * @return string
     */
    protected function fulltextTerm($value) {
        $cleaned = preg_replace('/[+\-*~<>()"@]+/u',' ',$value);
        $words   = preg_split('/\s+/u',trim($cleaned),-1,PREG_SPLIT_NO_EMPTY);

        return implode(' ',array_map(function($word) {
            return '+'.$word.'*';
        },$words));
    }

    // HELPERS ---------------------------------------------------------------------------------------------------------
    /**
     * Shortcut helper method for blade views
     * so in view it can be checked wether or not a filter was set
     * with $filters->has('fieldname')
     *
     * Method should be extended for each field that is used in the filter view
     *
     * @param $keyOne String, for arrays use name of array
     * @param $keyTwo
     * @return bool
     */
    public function has($keyOne, $keyTwo = null) {
        // these are all arrays
        if ($keyOne == 'types' || $keyOne == 'contract_types' || $keyOne == 'nuts') {
            return $this->request()->has($keyOne) && in_array($keyTwo,(array) $this->request()->input($keyOne));
        }

        // this one is not
        if ($keyOne == 'date_type') {
            return $this->request()->has($keyOne) && $this->request()->input($keyOne) === $keyTwo;
        }

        return $this->request()->has($keyOne);
    }

    /**
     * @return bool
     */
    public function hasAny() {
        return count($this->appliedFilters) > 0;
    }

    /**
     * @param $field
     * @return bool|null
     */
    public function isSortedBy($field) {
        return $this->sortedBy == $field ? $this->sortDirection : false;
    }

    /**
     * @param $field
     * @param $direction
     * @return string
     */
    public function makeSortUrl($field,$direction) {

        $url = url()->current();
        $params = $this->request()->except('sort');
        $params['sort'] = ($direction == 'desc' ? '-' : '') . $field;

        return $url . '?' . http_build_query($params);
    }

    /**
     * @return array
     */
    public function getAppliedFilters() {
        return $this->appliedFilters;
    }
}

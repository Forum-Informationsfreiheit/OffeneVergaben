<?php

namespace App\Http\Filters;

use App\CPV;
use App\DatasetType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Kblais\QueryFilter\QueryFilter;

class DatasetFilter extends QueryFilter
{
    protected $filters = [];

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

    public function sort($field) {

        // TODO there needs to be a white list of sortable parameters!

        $this->sortDirection = substr($field, 0, 1) == '-' ? 'desc' : 'asc';
        $this->sortedBy = substr($field, 0, 1) == '-' ? substr($field,1) : $field;

        if ($this->sortedBy == 'offeror') {
            return $this->builder->orderBy('offerors.name',$this->sortDirection);
        }

        if ($this->sortedBy == 'contractor') {
            return $this->builder->orderBy('contractors.name',$this->sortDirection);
        }

        if ($this->sortedBy == 'cpv') {
            return $this->builder->orderBy('datasets.cpv_code',$this->sortDirection);
        }

        return $this->builder->orderBy($this->sortedBy,$this->sortDirection);
    }

    public function types($values) {
        $count = count($values);

        if ($count == 0 || $count >= 2) {
            // multi checkbox
            // if 2 of 2 options are selected then that means everything is selected
            // --> no need for filtering, just take 'all', we achieve that by doing nothing
            return $this->builder;
        }

        $this->filters[] = 'types';

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
        $count = count($values);

        if ($count == 0 || $count >= 3) {
            // do nothing
            return $this->builder;
        }

        $this->filters[] = 'contract_types';

        $contractTypes = [];

        if (in_array('works',$values)) {
            $contractTypes[] = 'WORKS';
        }
        if (in_array('services',$values)) {
            $contractTypes[] = 'SERVICES';
        }
        if (in_array('supplies',$values)) {
            $contractTypes[] = 'SUPPLIES';
        }

        if (count($contractTypes) > 0) {
            return $this->builder->whereIn('contract_type',$contractTypes);
        }

        // ignore any other value
        return $this->builder;
    }

    public function volumeFrom($value) {
        if (!is_numeric($value)) {
            return $this->builder;
        }

        $this->filters[] = 'volume_from';

        return $this->builder->where('val_total','>=',$value * 100);
    }

    public function volumeTo($value) {
        if (!is_numeric($value)) {
            return $this->builder;
        }

        $this->filters[] = 'volume_to';

        return $this->builder->where('val_total','<=',$value * 100);
    }

    public function cpv($value) {
        if (!intval($value)) {
            return $this->builder;
        }

        $like = substr($value,-1) === "*";

        $this->filters[] = 'cpv';

        // trim, but leave at least 2 characters
        $value = $like ? rtrim(rtrim($value,'*'),'0') : rtrim($value,'0');
        $value = strlen($value) == 1 ? $value . '0' : $value;

        if ($like || $this->request->has('cpv_like')) {
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
        if (!array_key_exists($this->request->input('date_type'),$this->datesMap)) {
            return $this->builder;
        }

        try {
            // input is DATE only. so carbon datetime will be <yyyy-mm-dd> 00:00:00
            // time of 00:00:00 is perfect as it is the beginning of the day and we want
            // to include the whole day
            $dateFrom  = Carbon::parse($value);
            $dateField = $this->datesMap[$this->request->input('date_type')];
            $this->filters[] = 'date_from';

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
        if (!array_key_exists($this->request->input('date_type'),$this->datesMap)) {
            return $this->builder;
        }

        try {
            // input is DATE only. so carbon datetime will be <yyyy-mm-dd> 00:00:00
            // to include the whole day we want to set the time to 23:59:59
            $dateTo  = Carbon::parse($value)->setHour(23)->setMinute(59)->setSecond(59);
            $dateField = $this->datesMap[$this->request->input('date_type')];
            $this->filters[] = 'date_to';

            return $this->builder->where($dateField,'<=',$dateTo);

        } catch(\Exception $ex) {
            return $this->builder;
        }
    }

    public function dateType($value) {
        // this param does nothing on its own
        // value of $this->request('date_param') will be used in dateFrom/dateTo methods
    }

    /**
     * Shortcut helper method for blade views
     * so in view it can be checked wether or not a filter was set
     * with $filters->has('fieldname')
     *
     * @param $key
     */

    /**
     * Method should be extended for each field that is used in the filter view
     *
     * @param $keyOne String, for arrays use name of array
     * @param $keyTwo
     * @return bool
     */
    public function has($keyOne, $keyTwo = null) {
        if ($keyOne == 'types') {
            return $this->request->has($keyOne) && in_array($keyTwo,$this->request->input($keyOne));
        }

        if ($keyOne == 'contract_types') {
            return $this->request->has($keyOne) && in_array($keyTwo,$this->request->input($keyOne));
        }

        if ($keyOne == 'date_type') {
            return $this->request->has($keyOne) && $this->request->input($keyOne) === $keyTwo;
        }

        return $this->request->has($keyOne);
    }

    public function hasAny() {
        return count($this->filters) > 0;
    }

    public function isSortedBy($field) {
        return $this->sortedBy == $field ? $this->sortDirection : false;
    }

    public function makeSortUrl($field,$direction) {

        $url = url()->current();
        $params = $this->request->except('sort');
        $params['sort'] = ($direction == 'desc' ? '-' : '') . $field;

        return $url . '?' . http_build_query($params);
    }
}
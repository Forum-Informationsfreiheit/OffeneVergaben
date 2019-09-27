<?php

namespace App\Http\Filters;

use App\DatasetType;
use Illuminate\Database\Query\Builder;
use Kblais\QueryFilter\QueryFilter;

class DatasetFilter extends QueryFilter
{
    protected $filters = [];

    // only single attribute sort implemented atm
    protected $sortedBy = null;
    protected $sortDirection = null;

    public function sort($field) {

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
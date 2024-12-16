<?php

namespace App\Http\Filters;

use App\DatasetType;
use Illuminate\Database\Query\Builder;
use Kblais\QueryFilter\QueryFilter;

class ContractorFilter extends QueryFilter
{
    public static $sortable = [
        'name',
        'count',
        'sum'
    ];

    protected $filters = [];

    // only single attribute sort implemented atm
    protected $sortedBy = null;
    protected $sortDirection = null;

    public function sort($field) {
        // unknown sort attribute? return early
        if ((substr($field, 0, 1) !== '-' && !in_array($field,self::$sortable)) ||
            (substr($field, 0, 1) === '-' && !in_array(substr($field,1),self::$sortable))) { // check prefix (desc sorting)
            return $this->builder;
        }

        $this->sortDirection = substr($field, 0, 1) == '-' ? 'desc' : 'asc';
        $this->sortedBy = substr($field, 0, 1) == '-' ? substr($field,1) : $field;

        if ($this->sortedBy == 'name') {
            return $this->builder->orderBy('organizations.name',$this->sortDirection);
        }
        if ($this->sortedBy == 'count') {
            return $this->builder->orderBy('datasets_count',$this->sortDirection);
        }
        if ($this->sortedBy == 'sum') {
            return $this->builder->orderBy('sum_val_total',$this->sortDirection);
        }

        return $this->builder->orderBy($this->sortedBy,$this->sortDirection);
    }

    /**
     * Method should be extended for each field that is used in the filter view
     *
     * @param $keyOne String, for arrays use name of array
     * @param $keyTwo
     * @return bool
     */
    public function has($keyOne, $keyTwo = null) {
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

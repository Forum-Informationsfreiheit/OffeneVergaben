<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kblais\QueryFilter\FilterableTrait;

class Contractor extends Model
{
    use FilterableTrait;

    protected $casts = [
        'is_extra' => 'boolean',
    ];

    public function organization() {
        return $this->belongsTo('App\Organization');
    }

    public static function indexQuery() {
        $query = self::select([
            'contractors.organization_id',
            DB::raw('count(*) as datasets_count'),
            DB::raw('sum(val_total) as sum_val_total')
        ]);
        $query->join('datasets','contractors.dataset_id','=','datasets.id');
        $query->join('organizations','contractors.organization_id','=','organizations.id');
        $query->where('datasets.is_current_version',1)->where('datasets.disabled_at',null);
        // note actually there is no need to group on organization name
        // but newer mysql version needs it to be there so we can select it, or order by it
        $query->groupBy('contractors.organization_id','organizations.name');

        //$query->orderBy('datasets_count','desc');

        return $query;
    }

    /**
     * Top ten listing either by count of contracts or total monetary volume of contracts.
     *
     * @param $type String, "count" or "sum"
     *
     * @return mixed
     */
    public static function bigFishQuery($type) {
        $query = null;

        if ($type == 'count') {
            $query = self::select(['contractors.organization_id', DB::raw('count(*) as datasets_count')]);
            $query->join('datasets','contractors.dataset_id','=','datasets.id');
            $query->join('dataset_types','datasets.type_code','=','dataset_types.code');
            $query->where('dataset_types.end',1);
            $query->where('datasets.is_current_version',1)->where('datasets.disabled_at',null);
            $query->groupBy('contractors.organization_id');
            $query->orderBy('datasets_count','desc');
        }

        if ($type == 'sum') {
            $query = self::select(['contractors.organization_id', DB::raw('sum(val_total) as sum_total_val')]);
            $query->join('datasets','contractors.dataset_id','=','datasets.id');
            $query->join('dataset_types','datasets.type_code','=','dataset_types.code');
            $query->where('dataset_types.end',1);
            $query->where('datasets.is_current_version',1)->where('datasets.disabled_at',null);
            $query->groupBy('contractors.organization_id');
            $query->orderBy('sum_total_val','desc');
        }

        return $query;
    }

    public function toString() {
        $values = [
            $this->national_id,
            $this->name,
        ];

        return implode(' | ',array_filter($values));
    }

    public function toHtmlString() {
        $values = [
            $this->national_id,
            $this->name,
        ];

        return implode('<br>',array_filter($values));
    }
}

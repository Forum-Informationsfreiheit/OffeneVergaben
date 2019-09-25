<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kblais\QueryFilter\FilterableTrait;

class Contractor extends Model
{
    use FilterableTrait;

    /*
    protected $casts = [
        'is_extra' => 'boolean',
    ];
    */

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
        $query->where('datasets.is_current_version',1);
        // note actually there is no need to group on organization name
        // but newer mysql version needs it to be there so we can select it, or order by it
        $query->groupBy('contractors.organization_id','organizations.name');

        //$query->orderBy('datasets_count','desc');

        return $query;
    }

    public static function bigFishQuery() {
        $query = self::select(['contractors.organization_id', DB::raw('count(*) as datasets_count')]);
        $query->join('datasets','contractors.dataset_id','=','datasets.id');
        $query->where('datasets.is_current_version',1);
        $query->groupBy('contractors.organization_id');
        $query->orderBy('datasets_count','desc');

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

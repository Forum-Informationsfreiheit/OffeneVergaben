<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Contractor extends Model
{
    /*
    protected $casts = [
        'is_extra' => 'boolean',
    ];
    */

    public function organization() {
        return $this->belongsTo('App\Organization');
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

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Offeror extends Model
{
    protected $casts = [
        'is_extra' => 'boolean',
    ];

    public function scopeOrganization($query, $id) {
        return $query->where('organization_id',$id);
    }

    public function organization() {
        return $this->belongsTo('App\Organization');
    }

    public function dataset() {
        return $this->belongsTo('App\Dataset');
    }

    public static function bigFishQuery() {
        $query = self::select(['offerors.organization_id', DB::raw('count(*) as datasets_count')]);
        $query->join('datasets','offerors.dataset_id','=','datasets.id');
        $query->where('datasets.is_current_version',1);
        $query->groupBy('offerors.organization_id');
        $query->orderBy('datasets_count','desc');

        return $query;
    }

    public function toString() {
        $values = [
            $this->national_id,
            $this->name,
            $this->domain,
            $this->phone,
            $this->email,
            $this->contact,
            $this->reference_number
        ];

        return implode(' | ',array_filter($values));
    }

    public function toHtmlString() {
        $values = [
            $this->national_id,
            $this->name,
            $this->domain,
            $this->phone,
            $this->email,
            $this->contact,
            $this->reference_number
        ];

        return implode('<br>',array_filter($values));
    }
}

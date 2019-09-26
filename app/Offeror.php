<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kblais\QueryFilter\FilterableTrait;

class Offeror extends Model
{
    use FilterableTrait;

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

    public static function indexQuery() {
        $query = self::select([
            'offerors.organization_id',
            DB::raw('count(*) as datasets_count'),
            DB::raw('sum(val_total) as sum_val_total')
        ]);
        $query->join('datasets','offerors.dataset_id','=','datasets.id');
        $query->join('organizations','offerors.organization_id','=','organizations.id');
        $query->where('datasets.is_current_version',1);
        // note actually there is no need to group on organization name
        // but newer mysql version needs it to be there so we can select it, or order by it
        $query->groupBy('offerors.organization_id','organizations.name');

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
            $query = self::select(['offerors.organization_id', DB::raw('count(*) as datasets_count')]);
            $query->join('datasets','offerors.dataset_id','=','datasets.id');
            $query->where('datasets.is_current_version',1);
            $query->groupBy('offerors.organization_id');
            $query->orderBy('datasets_count','desc');
        }

        if ($type == 'sum') {
            $query = self::select(['offerors.organization_id', DB::raw('sum(val_total) as sum_total_val')]);
            $query->join('datasets','offerors.dataset_id','=','datasets.id');
            $query->where('datasets.is_current_version',1);
            $query->groupBy('offerors.organization_id');
            $query->orderBy('sum_total_val','desc');
        }

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

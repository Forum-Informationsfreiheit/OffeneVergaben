<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        return $this->belongsTo('App\Models\Organization');
    }

    public function dataset() {
        return $this->belongsTo('App\Models\Dataset');
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

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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

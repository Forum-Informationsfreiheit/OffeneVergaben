<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contractor extends Model
{
    protected $casts = [
        'is_extra' => 'boolean',
    ];

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

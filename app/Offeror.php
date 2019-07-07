<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Offeror extends Model
{
    protected $casts = [
        'is_extra' => 'boolean',
    ];

    public function toString() {
        $values = [
            $this->national_id,
            $this->name,
            $this->domain,
            $this->phone,
            $this->email,
            $this->contact
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
            $this->contact
        ];

        return implode('<br>',array_filter($values));
    }
}

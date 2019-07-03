<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Offeror extends Model
{
    protected $casts = [
        'is_extra' => 'boolean',
    ];
}

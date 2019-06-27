<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Datasource extends Model
{
    public function origin() {
        return $this->belongsTo('App\Origin');
    }
}

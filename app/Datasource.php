<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Datasource extends Model
{
    protected $content;

    public function origin() {
        return $this->belongsTo('App\Origin');
    }

    public function dataset() {
        return $this->hasOne('App\Dataset');
    }
}
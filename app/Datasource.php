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

    public function datasets() {
        return $this->hasMany('App\Dataset');
    }

    public function dataset() {
        return $this->hasOne('App\Dataset')->where('is_current_version',1);
    }
}
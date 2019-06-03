<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    public function origin() {
        return $this->belongsTo('App\Origin');
    }
}

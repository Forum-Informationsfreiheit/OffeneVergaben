<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metaset extends Model
{
    public function datasets() {
        return $this->hasMany('App\Models\Dataset');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CPV extends Model
{
    protected $table = 'cpvs';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public function toString() {
        return $this->code . ' ' . $this->name;
    }

    public function datasets() {
        return $this->belongsToMany('App\Dataset','cpv_dataset','cpv_code','dataset_id');
    }
}

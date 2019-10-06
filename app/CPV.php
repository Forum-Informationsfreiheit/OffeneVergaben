<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CPV extends Model
{
    const STR_CODE_LENGTH = 8;   // all cpv codes are length 8

    protected $table = 'cpvs';

    protected $appends = [ 'level' ];

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $timestamps = false;

    public function toString() {
        return $this->code . ' ' . $this->name;
    }

    public function datasets() {
        return $this->belongsToMany('App\Dataset','cpv_dataset','cpv_code','dataset_id');
    }

    public function getLevelAttribute() {

        $temp = rtrim($this->code,'0');
        $len  = strlen($temp);

        // level 1 does not make sense, level 2 is the lowest it can get

        return $len === 1 ? 2 : $len;
    }
}

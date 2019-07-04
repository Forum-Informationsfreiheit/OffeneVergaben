<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    protected $dates = [
        'date_start',
        'date_end',
        'date_first_publication',
        'deadline_standstill',
        'datetime_last_change'
    ];

    public function cpvs() {
        return $this->belongsToMany('App\CPV','cpv_dataset','dataset_id','cpv_code');
    }
}

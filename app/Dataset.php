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
        'datetime_receipt_tenders',
        'datetime_last_change'
    ];

    public function cpvs() {
        return $this->belongsToMany('App\CPV','cpv_dataset','dataset_id','cpv_code');
    }

    public function procedures() {
        return $this->belongsToMany('App\Procedure','dataset_procedure','dataset_id','procedure_code');
    }
}

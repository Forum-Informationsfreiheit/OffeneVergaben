<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dataset extends Model
{
    protected $xml;

    protected $casts = [
        'is_current_version' => 'boolean',
    ];

    protected $dates = [
        'date_start',
        'date_end',
        'date_first_publication',
        'date_conclusion_contract',
        'deadline_standstill',
        'datetime_receipt_tenders',
        'datetime_last_change'
    ];

    public function scopeCurrent($query) {
        return $query->where('is_current_version',1);
    }

    public function datasource() {
        return $this->belongsTo('App\Datasource');
    }

    public function type() {
        return $this->belongsTo('App\DatasetType','type_code');
    }

    public function offeror() {
        return $this->hasOne('App\Offeror')->where('is_extra',0);
    }

    public function offerors() {
        return $this->hasMany('App\Offeror');
    }

    public function contractors() {
        return $this->hasMany('App\Contractor');
    }

    public function cpvs() {
        return $this->belongsToMany('App\CPV','cpv_dataset','dataset_id','cpv_code');
    }

    public function procedures() {
        return $this->belongsToMany('App\Procedure','dataset_procedure','dataset_id','procedure_code');
    }

    public function getValTotalFormattedAttribute() {
        if (!$this->val_total) {
            return null;
        }

        return $this->formatMoney($this->val_total);
    }
    public function getValTotalBeforeFormattedAttribute() {
        if (!$this->val_total_before) {
            return null;
        }

        return $this->formatMoney($this->val_total_before);
    }
    public function getValTotalAfterFormattedAttribute() {
        if (!$this->val_total_after) {
            return null;
        }

        return $this->formatMoney($this->val_total_after);
    }
    public function getXmlAttribute() {
        if (!$this->xml) {
            $this->xml = DB::table('scraper_results')
                ->select(['content'])
                ->where('id',$this->result_id)->pluck('content')->first();
        }
        return $this->xml;
    }
    public function getOtherVersionsAttribute() {
        $others = $this->datasource->datasets()->where('version','!=',$this->version)->orderBy('version','asc')->get();

        return count($others) ? $others : null;
    }

    protected function formatMoney($value) {
        return number_format($value / 100,2,',','.');
    }
}

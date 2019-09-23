<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class Dataset extends Model
{
    protected $xml;

    protected $casts = [
        'is_current_version' => 'boolean',
        'threshold' => 'boolean',
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

    /**
     * Some attributes of a dataset have different meaning/translations
     * depending on type.
     *
     * @param $attribute
     *
     * @return string
     */
    public static function labelStatic($attribute,$type) {
        $translationGeneralKey = 'dataset.'.$attribute;

        if (Lang::has($translationGeneralKey.'.'.$type)) {
            return Lang::get($translationGeneralKey.'.'.$type);
        } else {
            // this should always be defined, if not, someone was lazy
            return $translationGeneralKey;
        }
    }

    /**
     * Shortcut for static version. Handy if you have a $dataset at hand.
     *
     * @param $attribute
     *
     * @return string
     */
    public function label($attribute) {
        return self::labelStatic($attribute,$this->type_code);
    }

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

    public function contractor() {
        return $this->hasOne('App\Contractor');
    }

    public function contractors() {
        return $this->hasMany('App\Contractor');
    }

    public function cpv() {
        return $this->belongsTo('App\CPV','cpv_code');
    }

    public function nuts() {
        return $this->belongsTo('App\NUTS','nuts_code');
    }

    public function cpvs() {
        return $this->belongsToMany('App\CPV','cpv_dataset','dataset_id','cpv_code');
    }

    public function procedures() {
        return $this->belongsToMany('App\Procedure','dataset_procedure','dataset_id','procedure_code');
    }
/*
    public function getContractorAttribute() {

    }
*/
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

    public function getTitleFormattedAttribute() {
        if (!$this->title) {
            return $this->title;
        }

        return nl_to_br($this->title);
    }

    public function getDescriptionFormattedAttribute() {
        if (!$this->description) {
            return $this->description;
        }

        return nl_to_br($this->description);
    }

    public function getInfoModificationsFormattedAttribute() {
        if (!$this->info_modifications) {
            return $this->info_modifications;
        }

        return nl_to_br($this->info_modifications);
    }

    public function getJustificationFormattedAttribute() {
        if (!$this->justification) {
            return $this->justification;
        }

        return nl_to_br($this->justification);
    }

    public function getProcedureDescriptionFormattedAttribute() {
        if (!$this->procedure_description) {
            return $this->procedure_description;
        }

        return nl_to_br($this->procedure_description);
    }

    protected function formatMoney($value) {
        return number_format($value / 100,2,',','.');
    }
}

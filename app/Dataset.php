<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Kblais\QueryFilter\FilterableTrait;

class Dataset extends Model
{
    use FilterableTrait;

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
        'datetime_last_change',
        'item_lastmod'
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
     * Use query builder to build up the index query for /aufträge main table
     *
     * @param array $options
     *
     * @return mixed
     */
    public static function indexQuery($options = []) {
        $defaultOptions = [
            'allOfferors' => false,     // ignore extra offerors by default
            'allContractors' => false,  // ignore extra contractors by default
        ];
        $options = array_merge($defaultOptions, $options);

        // build up the basic query here
        // do the micro management for where clause and order clause later
        $query = self::select(['datasets.id']);
        // direct join here as every dataset as at least one offeror
        $query->join('offerors',function($join) use($options) {
            $join->on('datasets.id', '=', 'offerors.dataset_id');
            if (!($options['allOfferors'])) {
                $join->where('offerors.is_extra','=',0);
            }
        });
        // left join here because not every dataset has a contractor
        $query->leftJoin('contractors',function($join) use ($options) {
            $join->on('datasets.id', '=', 'contractors.dataset_id');
            if (!($options['allContractors'])) {
                $join->where('contractors.is_extra','=',0);
            }
        });

        // 20200409 - wieso war das bisher nicht im query??????
        $query->where('is_current_version',1);

        return $query;
    }

    /**
     * Very simple title and description search.
     * Adds information as is_offeror and is_contractor about the type of organization.
     *
     * @param $tokens
     * @return mixed
     */
    public static function searchTitleAndDescriptionQuery($tokens) {

        if (!$tokens) {
            return null;
        }

        $query = self::select([
            'datasets.id',
            'datasets.title',
            'datasets.description',
            //DB::raw('(SELECT 1 FROM offerors    o WHERE o.organization_id = organizations.id LIMIT 1) as "is_offeror"'),
            //DB::raw('(SELECT 1 FROM contractors c WHERE c.organization_id = organizations.id LIMIT 1) as "is_contractor"')
        ]);

        $query->where(function($q) use ($tokens) {
            foreach($tokens as $token) {
                $q->where('title','like','%'. $token .'%');
            }
        });
        $query->orWhere(function($q) use ($tokens) {
            foreach($tokens as $token) {
                $q->where('description','like','%'. $token .'%');
            }
        });

        return $query;
    }

    /**
     * Use this method if order is important when loading datasets by id
     *
     * @param array $orderedIds
     * @return mixed
     */
    public static function loadInOrder($orderedIds) {
        if (!count($orderedIds)) {
            // nothing to load? return empty query
            return Dataset::query();
        }

        $str = join(',',$orderedIds);

        return Dataset::whereIn('id',$orderedIds)
            ->orderByRaw(DB::raw("FIELD(id, $str)")); // https://stackoverflow.com/a/26704767/718980
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

    /*
    public function datasource() {
        return $this->belongsTo('App\Datasource');
    }
    */

    public function type() {
        return $this->belongsTo('App\DatasetType','type_code');
    }

    public function metaset() {
        return $this->belongsTo('App\Metaset');
    }

    public function scraperKerndaten() {
        return $this->belongsTo('App\ScraperKerndaten','scraper_kerndaten_id','id');
    }

    public function offeror() {
        return $this->hasOne('App\Offeror')->where('is_extra',0);
    }

    public function offerors() {
        return $this->hasMany('App\Offeror');
    }

    public function offerorsAdditional(){
        return $this->hasMany('App\Offeror')->where('is_extra',1);
    }

    public function contractor() {
        return $this->hasOne('App\Contractor')->where('is_extra',0);
    }

    public function contractors() {
        return $this->hasMany('App\Contractor')->where('is_extra',1);
    }

    public function contractorsAdditional() {
        return $this->hasMany('App\Contractor');
    }

    public function nuts() {
        return $this->belongsTo('App\NUTS','nuts_code');
    }

    public function cpv() {
        return $this->belongsTo('App\CPV','cpv_code');
    }

    public function cpvs() {
        return $this->belongsToMany('App\CPV','cpv_dataset','dataset_id','cpv_code');
    }

    public function cpvsAdditional() {
        return $this->belongsToMany('App\CPV','cpv_dataset','dataset_id','cpv_code')->where('main',0);
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
        $others = $this->metaset->datasets()->where('version','!=',$this->version)->orderBy('version','asc')->get();

        return count($others) ? $others : null;
    }

    public function getVersionLinksAttribute() {

        $otherVersions = $this->otherVersions;

        if (count($otherVersions) === 0) {
            return '';
        }

        $html = '<ul>';

        foreach($this->otherVersions as $other) {
            $html .= '<li>';
            $html .= '<a href="'.route('public::auftrag',$other->id).'">'.$other->version.'</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
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
        return ui_format_money($value);
    }
}

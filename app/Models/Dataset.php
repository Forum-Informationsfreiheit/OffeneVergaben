<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Kblais\QueryFilter\Filterable;

/**
 * App\Models\Dataset
 *
 * @property int $id
 * @property int|null $metaset_id
 * @property int $version
 * @property bool $is_current_version
 * @property int|null $scraper_kerndaten_id
 * @property \Illuminate\Support\Carbon|null $disabled_at
 * @property string|null $type_code
 * @property string|null $cpv_code
 * @property string|null $nuts_code
 * @property string|null $url_document
 * @property int|null $url_is_restricted
 * @property string|null $url_participation
 * @property string|null $contract_type
 * @property string|null $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $date_start
 * @property \Illuminate\Support\Carbon|null $date_end
 * @property int|null $duration
 * @property \Illuminate\Support\Carbon|null $datetime_receipt_tenders
 * @property int|null $is_lot
 * @property int|null $is_framework
 * @property \Illuminate\Support\Carbon|null $date_conclusion_contract
 * @property int|null $nb_tenders_received
 * @property int|null $nb_sme_tender
 * @property int|null $nb_sme_contractor
 * @property int|null $val_total
 * @property int|null $val_total_before
 * @property int|null $val_total_after
 * @property string|null $info_modifications
 * @property string|null $justification
 * @property \Illuminate\Support\Carbon|null $date_first_publication
 * @property \Illuminate\Support\Carbon|null $datetime_last_change
 * @property \Illuminate\Support\Carbon|null $deadline_standstill
 * @property int|null $rd_notification
 * @property string|null $ocm_title
 * @property string|null $ocm_contract_type
 * @property string|null $procedure_description
 * @property bool|null $threshold
 * @property string|null $url_revocation
 * @property string|null $url_revocation_statement
 * @property int|null $nb_participants
 * @property int|null $nb_participants_sme
 * @property string|null $item_lastmod
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contractor|null $contractor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contractor> $contractors
 * @property-read int|null $contractors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contractor> $contractorsAdditional
 * @property-read int|null $contractors_additional_count
 * @property-read \App\Models\CPV|null $cpv
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CPV> $cpvs
 * @property-read int|null $cpvs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CPV> $cpvsAdditional
 * @property-read int|null $cpvs_additional_count
 * @property-read mixed $description_formatted
 * @property-read mixed $info_modifications_formatted
 * @property-read mixed $justification_formatted
 * @property-read mixed $other_versions
 * @property-read mixed $procedure_description_formatted
 * @property-read mixed $title_formatted
 * @property-read mixed $val_total_after_formatted
 * @property-read mixed $val_total_before_formatted
 * @property-read mixed $val_total_formatted
 * @property-read mixed $version_links
 * @property-read mixed $xml
 * @property-read \App\Models\Metaset|null $metaset
 * @property-read \App\Models\NUTS|null $nuts
 * @property-read \App\Models\Offeror|null $offeror
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Offeror> $offerors
 * @property-read int|null $offerors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Offeror> $offerorsAdditional
 * @property-read int|null $offerors_additional_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Procedure> $procedures
 * @property-read int|null $procedures_count
 * @property-read \App\Models\ScraperKerndaten|null $scraperKerndaten
 * @property-read \App\Models\DatasetType|null $type
 * @method static Builder|Dataset current()
 * @method static Builder|Dataset enabled()
 * @method static Builder|Dataset filter(\Kblais\QueryFilter\QueryFilter $filters)
 * @method static Builder|Dataset newModelQuery()
 * @method static Builder|Dataset newQuery()
 * @method static Builder|Dataset query()
 * @method static Builder|Dataset whereContractType($value)
 * @method static Builder|Dataset whereCpvCode($value)
 * @method static Builder|Dataset whereCreatedAt($value)
 * @method static Builder|Dataset whereDateConclusionContract($value)
 * @method static Builder|Dataset whereDateEnd($value)
 * @method static Builder|Dataset whereDateFirstPublication($value)
 * @method static Builder|Dataset whereDateStart($value)
 * @method static Builder|Dataset whereDatetimeLastChange($value)
 * @method static Builder|Dataset whereDatetimeReceiptTenders($value)
 * @method static Builder|Dataset whereDeadlineStandstill($value)
 * @method static Builder|Dataset whereDescription($value)
 * @method static Builder|Dataset whereDisabledAt($value)
 * @method static Builder|Dataset whereDuration($value)
 * @method static Builder|Dataset whereId($value)
 * @method static Builder|Dataset whereInfoModifications($value)
 * @method static Builder|Dataset whereIsCurrentVersion($value)
 * @method static Builder|Dataset whereIsFramework($value)
 * @method static Builder|Dataset whereIsLot($value)
 * @method static Builder|Dataset whereItemLastmod($value)
 * @method static Builder|Dataset whereJustification($value)
 * @method static Builder|Dataset whereMetasetId($value)
 * @method static Builder|Dataset whereNbParticipants($value)
 * @method static Builder|Dataset whereNbParticipantsSme($value)
 * @method static Builder|Dataset whereNbSmeContractor($value)
 * @method static Builder|Dataset whereNbSmeTender($value)
 * @method static Builder|Dataset whereNbTendersReceived($value)
 * @method static Builder|Dataset whereNutsCode($value)
 * @method static Builder|Dataset whereOcmContractType($value)
 * @method static Builder|Dataset whereOcmTitle($value)
 * @method static Builder|Dataset whereProcedureDescription($value)
 * @method static Builder|Dataset whereRdNotification($value)
 * @method static Builder|Dataset whereScraperKerndatenId($value)
 * @method static Builder|Dataset whereThreshold($value)
 * @method static Builder|Dataset whereTitle($value)
 * @method static Builder|Dataset whereTypeCode($value)
 * @method static Builder|Dataset whereUpdatedAt($value)
 * @method static Builder|Dataset whereUrlDocument($value)
 * @method static Builder|Dataset whereUrlIsRestricted($value)
 * @method static Builder|Dataset whereUrlParticipation($value)
 * @method static Builder|Dataset whereUrlRevocation($value)
 * @method static Builder|Dataset whereUrlRevocationStatement($value)
 * @method static Builder|Dataset whereValTotal($value)
 * @method static Builder|Dataset whereValTotalAfter($value)
 * @method static Builder|Dataset whereValTotalBefore($value)
 * @method static Builder|Dataset whereVersion($value)
 * @mixin \Eloquent
 */
class Dataset extends Model
{
    use Filterable;

    protected $xml;

    protected $casts = [
        'is_current_version' => 'boolean',
        'threshold' => 'boolean',
        // eForms only: [ 'sdk' => ..., 'groups' => [ [ 'title' => ..., 'entries' => [ [ 'field', 'label', 'value' ] ] ] ] ]
        'further_information' => 'array',
    ];

    protected $dates = [
        'date_start',
        'date_end',
        'date_first_publication',
        'date_conclusion_contract',
        'deadline_standstill',
        'datetime_receipt_tenders',
        'datetime_last_change',
        // 2021-12-15 item_lastmod is timestamp with microseconds and not a date - use accessor/mutator
        //'item_lastmod',
        'disabled_at'
    ];

    // SCOPES ----------------------------------------------------------------------------------------------------------
    /**
     * By default add a global scope to each query
     * Only select datasets that are 'enabled' (= NOT disabled)
     * @see https://laravel.com/docs/5.8/eloquent#global-scopes
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('not_disabled', function (Builder $builder) {
            $builder->where('disabled_at', null);
        });
    }

    public static function scopeCurrent($query) {
        return $query->where('is_current_version',1);
        //return $query->where('is_current_version',1);
    }

    public static function scopeEnabled($query) {
        return $query->where('disabled_at',null);
    }

    // RELATIONS -------------------------------------------------------------------------------------------------------
    public function type() {
        return $this->belongsTo('App\Models\DatasetType','type_code');
    }

    public function metaset() {
        return $this->belongsTo('App\Models\Metaset');
    }

    public function scraperKerndaten() {
        return $this->belongsTo('App\Models\ScraperKerndaten','scraper_kerndaten_id','id');
    }

    public function offeror() {
        return $this->hasOne('App\Models\Offeror')->where('is_extra',0);
    }

    public function offerors() {
        return $this->hasMany('App\Models\Offeror');
    }

    // ohne orderBy haengt die Reihenfolge vom Index ab, den MySQL waehlt: ueber ix_extra_namesort_ds
    // kommen die Zeilen nach Namen sortiert statt in der Reihenfolge der Quelle
    public function offerorsAdditional(){
        return $this->hasMany('App\Models\Offeror')->where('is_extra',1)->orderBy('id');
    }

    public function contractor() {
        return $this->hasOne('App\Models\Contractor')->where('is_extra',0);
    }

    public function contractors() {
        return $this->hasMany('App\Models\Contractor');
    }

    public function contractorsAdditional() {
        // siehe offerorsAdditional()
        return $this->hasMany('App\Models\Contractor')->where('is_extra',1)->orderBy('id');
    }

    public function nuts() {
        return $this->belongsTo('App\Models\NUTS','nuts_code');
    }

    public function cpv() {
        return $this->belongsTo('App\Models\CPV','cpv_code');
    }

    public function cpvs() {
        return $this->belongsToMany('App\Models\CPV','cpv_dataset','dataset_id','cpv_code');
    }

    public function cpvsAdditional() {
        return $this->belongsToMany('App\Models\CPV','cpv_dataset','dataset_id','cpv_code')->where('main',0);
    }

    public function procedures() {
        return $this->belongsToMany('App\Models\Procedure','dataset_procedure','dataset_id','procedure_code');
    }

    // ACCESSORS -------------------------------------------------------------------------------------------------------
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

    // 2021-12-15 item_lastmod is timestamp with microseconds and not a date - use accessor/mutator
    public function getItemLastmodAttribute($value) {
        return $value ? Carbon::createFromFormat('Y-m-d H:i:s.u', $value) : null;
    }
    public function setItemLastmodAttribute($value) {
        $this->attributes['item_lastmod'] = $value ? $value->format('Y-m-d H:i:s.u') : null;
    }

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
            'joinOfferors' => true,     // if true offerors table will be joined on query (more data, but slower)
            'joinContractors' => true,  // if true contractors table will be joined on query (more data, but slower)
            'allOfferors' => false,     // ignore extra offerors by default (only avail if joinOfferors === true)
            'allContractors' => false,  // ignore extra contractors by default (only avail if joinContractors === true)
        ];
        $options = array_merge($defaultOptions, $options);

        // build up the basic query here
        // do the micro management for where clause and order clause later
        $query = self::select(['datasets.id']);

        if ($options['joinOfferors']) {
            // direct join here as every dataset as at least one offeror
            $query->join('offerors',function($join) use($options) {
                $join->on('datasets.id', '=', 'offerors.dataset_id');
                if (!($options['allOfferors'])) {
                    $join->where('offerors.is_extra','=',0);
                }
            });
        }

        if ($options['joinContractors']) {
            // left join here because not every dataset has a contractor
            $query->leftJoin('contractors',function($join) use ($options) {
                $join->on('datasets.id', '=', 'contractors.dataset_id');
                if (!($options['allContractors'])) {
                    $join->where('contractors.is_extra','=',0);
                }
            });
        }

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

    /**
     * The dataset type for display: for Kerndaten code and description of the type, for eForms the notice
     * subtype, e.g. "eForms E4: Freiwillige Vergabebekanntmachung".
     *
     * @return string
     */
    public function typeToString() {
        if ($this->source_format !== 'eforms') {
            return $this->type ? $this->type->toString() : (string)$this->type_code;
        }

        $key = 'dataset.eforms_subtypes.'.$this->source_subtype;

        return $this->source_subtype && Lang::has($key) ?
            'eForms '.$this->source_subtype.': '.Lang::get($key) :
            trim('eForms-Bekanntmachung '.$this->source_subtype);
    }

/*
    public function getContractorAttribute() {

    }
*/

    protected function formatMoney($value) {
        return ui_format_money($value);
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Organization extends Model
{
    protected $casts = [
        'is_identified' => 'boolean',
    ];

    public function offerors() {
        return $this->hasMany('App\Offeror');
    }

    public function contractors() {
        return $this->hasMany('App\Contractor');
    }

    /**
     * Use this method if order is important when loading organizations by id
     * Almost duplicate of Dataset::loadInOrder (refactor?)
     *
     * @param $orderedIds
     * @return mixed
     */
    public static function loadInOrder($orderedIds) {
        $str = join(',',$orderedIds);

        return Organization::whereIn('id',$orderedIds)
            ->orderByRaw(DB::raw("FIELD(id, $str)")) // https://stackoverflow.com/a/26704767/718980
            ->get();
    }

    /**
     * @param $id string
     * @param $type string
     * @param $name string
     *
     * @return null|\App\Organization
     */
    public static function createFromType($id, $type, $name) {
        $type = is_string($type) ? strtoupper($type) : null;

        if ($type !== NationalIdParser::TYPE_FN
            && $type !== NationalIdParser::TYPE_GLN
            && $type !== NationalIdParser::TYPE_GKZ) {
            return null;
        }

        // new organization!
        $org = new self();
        $org->fn  = $type === NationalIdParser::TYPE_FN  ? $id : null;
        $org->gln = $type === NationalIdParser::TYPE_GLN ? $id : null;
        $org->gkz = $type === NationalIdParser::TYPE_GKZ ? $id : null;
        $org->name = $name;
        $org->is_identified = true;
        $org->save();

        return $org;
    }

    /**
     * Very simple organization name search implementation.
     * Adds information as is_offeror and is_contractor about the type of organization.
     *
     * @param $name
     * @return mixed
     */
    public static function searchNameQuery($tokens) {

        if (!$tokens) {
            return null;
        }

        /*
        $query = self::select([
            'organizations.*',
            DB::raw('(SELECT 1 FROM offerors    o WHERE o.organization_id = organizations.id LIMIT 1) as "is_offeror"'),
            DB::raw('(SELECT 1 FROM contractors c WHERE c.organization_id = organizations.id LIMIT 1) as "is_contractor"')
        ]);
        */

        $query = self::select([
            'organizations.*',
            DB::raw('(SELECT count(*) FROM offerors o
                        JOIN datasets d on o.dataset_id = d.id
                        WHERE o.organization_id = organizations.id and d.is_current_version = 1 and d.disabled_at is null LIMIT 1) as "is_offeror"'),
            DB::raw('(SELECT count(*) FROM contractors c
                        JOIN datasets d on c.dataset_id = d.id
                        WHERE c.organization_id = organizations.id and d.is_current_version = 1 and d.disabled_at is null LIMIT 1) as "is_contractor"')
        ]);

        foreach($tokens as $token) {
            $query->where('name','like','%'. $token .'%');
        }

        return $query;
    }

    /**
     * @param $name
     *
     * @return null|\App\Organization
     */
    public static function createFromUnknownType($id, $name) {
        if (!$id) {
            return null;
        }

        // new organization!
        $org = new self();
        $org->ukn = $id;
        $org->name = $name;
        $org->is_identified = false;
        $org->save();

        return $org;
    }

    /**
     * @param $name
     *
     * @return null|\App\Organization
     */
    public static function createGeneric($name) {
        if (!$name) {
            return null;
        }

        // new organization!
        $org = new self();
        $org->name = $name;
        $org->is_identified = false;
        $org->save();

        return $org;
    }

    /**
     *      1. GLN
     *      2. FN
     *      3. GKZ
     *      4. Other
     */
    public function getNationalIdAttribute() {
        if ($this->gln != null) {
            return $this->gln;
        }

        if ($this->fn != null) {
            return $this->fn;
        }

        if ($this->gkz != null) {
            return $this->gkz;
        }

        if ($this->ukn != null) {
            return $this->ukn;
        }

        return "?";
    }

    public function getNationalIdLabelAttribute() {
        if ($this->gln != null) {
            return "GLN";
        }

        if ($this->fn != null) {
            return "Firmenbuchnr.";
        }

        if ($this->gkz != null) {
            return "Gemeindekennzahl";
        }

        if ($this->ukn != null) {
            return "?";
        }

        return "";
    }

    public function getIdentifiersAttribute() {
        $identifiers = [];

        if ($this->gln != null) {
            $identifiers['gln'] = $this->gln;
        }

        if ($this->fn != null) {
            $identifiers['fn'] = $this->fn;
        }

        if ($this->gkz != null) {
            $identifiers['gkz'] = $this->gkz;
        }

        if ($this->ukn != null) {
            $identifiers['ukn'] = $this->ukn;
        }

        return $identifiers;
    }
}

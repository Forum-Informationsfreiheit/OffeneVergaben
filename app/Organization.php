<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}

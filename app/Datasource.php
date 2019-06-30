<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Datasource extends Model
{
    protected $content;

    public function origin() {
        return $this->belongsTo('App\Origin');
    }

    public function dataset() {
        return $this->hasOne('App\Dataset');
    }

    public function scopeUnprocessed($query) {
        return $query->doesntHave('dataset');
    }

    public function getContentAttribute() {
        if (!$this->content) {
            $this->loadContent();
        }

        return $this->content;
    }

    protected function loadContent() {

        // don't operate on non persistent datasources
        if (!$this->id) {
            return;
        }

        // kinda hacky to use direct DB Statements here,
        // maybe actually use an Eloquent Model for ScraperResults too ?!

        $result = DB::table('scraper_results')
            ->select(['content'])
            ->where('parent_reference_id',$this->origin->reference_id)
            ->where('reference_id',$this->reference_id)
            ->where('version',$this->version_scraped)
            ->first();

        if (!$result) {
            return;
        }

        $this->content = $result->content;
    }
}

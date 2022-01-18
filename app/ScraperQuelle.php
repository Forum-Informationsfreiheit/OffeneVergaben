<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScraperQuelle extends Model
{
    /**
     * The database connection used to connect to the scrapers database.
     *
     * @var string
     */
    protected $connection = 'mysql_scraper';

    protected $table = 'quellen';

    // RELATIONS -------------------------------------------------------------------------------------------------------
    /*
    public function author() {
        return $this->belongsTo('App\User','author_id');
    }
    */

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopeActive($query) {
        return $query->where('active',1);
    }
}

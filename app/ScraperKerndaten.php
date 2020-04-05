<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScraperKerndaten extends Model
{
    /**
     * The database connection used to connect to the scrapers database.
     *
     * @var string
     */
    protected $connection = 'mysql_scraper';

    protected $table = 'kerndaten';

    protected $dates = [
        'app_processed_at'
    ];

    public $timestamps = false;

    // RELATIONS -------------------------------------------------------------------------------------------------------
    // Sonderfall weil attribut ebenfalls quelle heisst, daher methode umbenannt auf rel_quelle (anstatt quelle)
    public function rel_quelle() {
        return $this->belongsTo('App\ScraperQuelle','quelle','alias');
    }

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopeUnprocessed($query) {
        return $query->where('app_processed_at',null)->where('app_dataset_id',null);
    }
}

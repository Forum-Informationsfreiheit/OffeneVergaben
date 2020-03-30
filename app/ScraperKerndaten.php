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

    // RELATIONS -------------------------------------------------------------------------------------------------------


    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopeUnprocessed($query) {
        return $query->where('app_processed_at',null)->where('app_dataset_id',null);
    }
}

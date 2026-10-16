<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ScraperQuelle
 *
 * @property int $id
 * @property string $alias
 * @property string|null $name
 * @property int $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle active()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperQuelle whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        return $this->belongsTo('App\Models\User','author_id');
    }
    */

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopeActive($query) {
        return $query->where('active',1);
    }
}

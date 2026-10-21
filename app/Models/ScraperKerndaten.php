<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ScraperKerndaten
 *
 * @property int $id
 * @property string $quelle
 * @property string $item_id
 * @property string|null $item_lastmod
 * @property int|null $version
 * @property string|null $xml
 * @property \Illuminate\Support\Carbon|null $app_processed_at
 * @property int|null $app_dataset_id
 * @property-read string $xml_formatted_html
 * @property-read \App\Models\ScraperQuelle|null $rel_quelle
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten unprocessed()
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereAppDatasetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereAppProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereItemLastmod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereQuelle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScraperKerndaten whereXml($value)
 * @mixin \Eloquent
 */
class ScraperKerndaten extends Model
{
    /**
     * The database connection used to connect to the scrapers database.
     *
     * @var string
     */
    protected $connection = 'mysql_scraper';

    protected $table = 'kerndaten';

    protected $casts = [
        'app_processed_at' => 'datetime',
    ];

    public $timestamps = false;

    // RELATIONS -------------------------------------------------------------------------------------------------------
    // Sonderfall weil attribut ebenfalls quelle heisst, daher methode umbenannt auf rel_quelle (anstatt quelle)
    public function rel_quelle() {
        return $this->belongsTo('App\Models\ScraperQuelle','quelle','alias');
    }

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopeUnprocessed($query) {
        return $query->where('app_processed_at',null)->where('app_dataset_id',null);
    }

    /**
     * For displaying (embedding in an html document) use the htmlentities(...) function
     *
     * @return string
     */
    public function getXmlFormattedHtmlAttribute() {

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($this->xml);

        $string = $doc->saveXML();

        return $string;
    }
}

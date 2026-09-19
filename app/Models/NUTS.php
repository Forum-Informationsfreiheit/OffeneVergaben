<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\NUTS
 *
 * @property string $code
 * @property string $name
 * @property int $level
 * @property string $country_code
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS query()
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NUTS whereName($value)
 * @mixin \Eloquent
 */
class NUTS extends Model
{
    protected $table = 'nuts';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public function toString() {
        return $this->code . ' ' . $this->name;
    }
}

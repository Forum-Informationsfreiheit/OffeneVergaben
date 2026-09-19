<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Procedure
 *
 * @property string $code
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|Procedure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Procedure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Procedure query()
 * @method static \Illuminate\Database\Eloquent\Builder|Procedure whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Procedure whereName($value)
 * @mixin \Eloquent
 */
class Procedure extends Model
{
    protected $table = 'procedures';

    protected $primaryKey = 'code';

    protected $keyType = 'string';
}

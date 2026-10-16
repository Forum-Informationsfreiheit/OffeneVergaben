<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DatasetType
 *
 * @property string $code
 * @property string $name
 * @property string $description
 * @property int $end
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType auftrag()
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType ausschreibung()
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType query()
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType whereEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DatasetType whereName($value)
 * @mixin \Eloquent
 */
class DatasetType extends Model
{
    protected $table = 'dataset_types';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $casts = [
        'end' => 'integer',
    ];

    public function scopeAuftrag($query) {
        return $query->where('end',1);
    }

    public function scopeAusschreibung($query) {
        return $query->where('end',0);
    }

    public function toString() {
        return $this->code . ' ' . $this->description;
    }
}

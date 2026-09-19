<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Metaset
 *
 * @property int $id
 * @property int $quellen_id
 * @property string $item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Dataset> $datasets
 * @property-read int|null $datasets_count
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset query()
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset whereQuellenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Metaset whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Metaset extends Model
{
    public function datasets() {
        return $this->hasMany('App\Models\Dataset');
    }
}

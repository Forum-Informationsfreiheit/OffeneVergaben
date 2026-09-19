<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Origin
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Origin active()
 * @method static \Illuminate\Database\Eloquent\Builder|Origin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Origin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Origin query()
 * @mixin \Eloquent
 */
class Origin extends Model
{
    protected $casts = [
        'scrape' => 'boolean'
    ];

    public function scopeActive($query) {
        return $query->where('scrape',1);
    }
}

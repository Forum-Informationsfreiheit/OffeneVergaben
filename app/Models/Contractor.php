<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kblais\QueryFilter\Filterable;

/**
 * App\Models\Contractor
 *
 * @property int $id
 * @property int $dataset_id
 * @property int|null $organization_id
 * @property bool $is_extra
 * @property string|null $national_id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor filter(\Kblais\QueryFilter\QueryFilter $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor query()
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereDatasetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereIsExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contractor whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Contractor extends Model
{
    use Filterable;

    protected $casts = [
        'is_extra' => 'boolean',
    ];

    public function organization() {
        return $this->belongsTo('App\Models\Organization');
    }

    public function toString() {
        $values = [
            $this->national_id,
            $this->name,
        ];

        return implode(' | ',array_filter($values));
    }

    public function toHtmlString() {
        $values = [
            $this->national_id,
            $this->name,
        ];

        return implode('<br>',array_filter($values));
    }
}

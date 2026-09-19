<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kblais\QueryFilter\Filterable;

/**
 * App\Models\Offeror
 *
 * @property int $id
 * @property int $dataset_id
 * @property int|null $organization_id
 * @property bool $is_extra
 * @property string|null $national_id
 * @property string|null $name
 * @property string|null $domain
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $contact
 * @property string|null $reference_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Dataset $dataset
 * @property-read \App\Models\Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror filter(\Kblais\QueryFilter\QueryFilter $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror organization($id)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror query()
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereDatasetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereIsExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Offeror whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Offeror extends Model
{
    use Filterable;

    protected $casts = [
        'is_extra' => 'boolean',
    ];

    public function scopeOrganization($query, $id) {
        return $query->where('organization_id',$id);
    }

    public function organization() {
        return $this->belongsTo('App\Models\Organization');
    }

    public function dataset() {
        return $this->belongsTo('App\Models\Dataset');
    }

    public function toString() {
        $values = [
            $this->national_id,
            $this->name,
            $this->domain,
            $this->phone,
            $this->email,
            $this->contact,
            $this->reference_number
        ];

        return implode(' | ',array_filter($values));
    }

    public function toHtmlString() {
        $values = [
            $this->national_id,
            $this->name,
            $this->domain,
            $this->phone,
            $this->email,
            $this->contact,
            $this->reference_number
        ];

        return implode('<br>',array_filter($values));
    }
}

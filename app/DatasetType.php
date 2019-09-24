<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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

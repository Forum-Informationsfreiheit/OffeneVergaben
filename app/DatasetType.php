<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DatasetType extends Model
{
    protected $table = 'dataset_types';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public function toString() {
        return $this->code . ' ' . $this->description;
    }
}

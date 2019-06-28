<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CPV extends Model
{
    protected $table = 'cpvs';

    protected $primaryKey = 'code';

    protected $keyType = 'string';
}

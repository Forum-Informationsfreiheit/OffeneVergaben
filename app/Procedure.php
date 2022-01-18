<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    protected $table = 'procedures';

    protected $primaryKey = 'code';

    protected $keyType = 'string';
}

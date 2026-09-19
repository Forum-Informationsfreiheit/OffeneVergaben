<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    protected $table = 'procedures';

    protected $primaryKey = 'code';

    protected $keyType = 'string';
}

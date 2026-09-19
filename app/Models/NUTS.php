<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NUTS extends Model
{
    protected $table = 'nuts';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public function toString() {
        return $this->code . ' ' . $this->name;
    }
}

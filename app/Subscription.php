<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    const TYPE_DATASET      = 'dataset';
    const TYPE_ORGANIZATION = 'organization';

    protected $dates = [
        'verified_at'
    ];

    // RELATIONS -------------------------------------------------------------------------------------------------------
    public function subscriber() {
        return $this->belongsTo('App\User','user_id');
    }

    // SCOPES ----------------------------------------------------------------------------------------------------------
    public static function scopeVerified($query) {
        return $query->whereNotNull('verified_at');
    }
}

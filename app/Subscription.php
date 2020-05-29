<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

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

    // ACCESSORS -------------------------------------------------------------------------------------------------------
    /**
     * Cancel URL --> CONFIRM cancelling of the subscription
     *
     * @return String signed cancel subscription url
     */
    public function getCancelUrlAttribute() {
        return URL::signedRoute(
            'public::cancel-subscription',
            [ 'id' => $this->id, 'email' => $this->subscriber->email ]
        );
    }

    /**
     * Unsubscribe URL --> directly unsubscribe (=destroy) subscription
     *
     * @return String signed unsubscribe url
     */
    public function getUnsubscribeUrlAttribute() {
        return URL::signedRoute(
            'public::unsubscribe',
            [ 'id' => $this->id, 'email' => $this->subscriber->email ]
        );
    }
}

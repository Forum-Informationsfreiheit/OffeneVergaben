<?php

namespace App\Models;

use App\Http\Controllers\SubscriptionController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // RELATIONS -------------------------------------------------------------------------------------------------------
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role() {
        return $this->belongsTo('App\Models\Role');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions() {
        return $this->hasMany('App\Models\Subscription');
    }

    // SCOPES ----------------------------------------------------------------------------------------------------------
    /**
     * @param $query
     * @return mixed
     */
    public static function scopeWithVerifiedEmail($query) {
        return $query->whereNotNull('email_verified_at');
    }

    // ACCESSORS -------------------------------------------------------------------------------------------------------
    /**
     * @return string
     */
    public function getInitialsAttribute() {
        $nameArray = $this->name ? explode(' ',$this->name) : [];

        $initials = count($nameArray) > 0 ? $nameArray[0][0] : '';
        $initials.= count($nameArray) > 1 ? $nameArray[1][0] : '';

        return $initials != '' ? $initials : '??';
    }

    /**
     * @return string
     */
    public function getFirstNameAttribute() {
        return $this->name ? explode(' ',$this->name)[0] : '';
    }

    /**
     * Cancel All URL --> CONFIRM cancelling of all subscriptions
     *
     * @return String signed cancel subscription url
     */
    public function getCancelAllSubscriptionsUrlAttribute() {
        return URL::signedRoute(
            'public::cancel-subscription',
            [ 'id' => SubscriptionController::ALL, 'email' => $this->email ]
        );
    }

    /**
     * Unsubscribe All URL --> directly unsubscribe (=destroy) subscription
     *
     * @return String signed unsubscribe url
     */
    public function getUnsubscribeAllUrlAttribute() {
        return URL::signedRoute(
            'public::unsubscribe',
            [ 'id' => SubscriptionController::ALL, 'email' => $this->email ]
        );
    }

    // OTHER -----------------------------------------------------------------------------------------------------------
    /**
     * @return bool
     */
    public function isSuperAdmin() {
        return $this->id === 1;
    }

    /**
     * @return bool
     */
    public function isAdmin() {
        return $this->role_id === Role::ADMIN;
    }

    /**
     * @return bool
     */
    public function isSubscriber() {
        return $this->role_id === Role::SUBSCRIBER;
    }

    /**
     * @param \App\Models\Subscription $subscription
     */
    public function sendSubscriptionVerificationNotification($subscription) {
        $this->notify(new \App\Notifications\VerifySubscription($subscription));
    }

    /**
     * @param \Illuminate\Support\Collection $subscriptions
     * @param array $updateInfo
     */
    public function sendSubscriptionUpdateSummaryNotification($subscriptions, $updateInfo) {
        // safety check: only allow verified subscriptions of this subscriber
        $subscriptions = $subscriptions->filter(function($subscription) {
            return $this->id === $subscription->user_id && $subscription->verified_at != null;
        });

        $this->notify(new \App\Notifications\SubscriptionUpdateSummary($subscriptions, $updateInfo));
    }
}

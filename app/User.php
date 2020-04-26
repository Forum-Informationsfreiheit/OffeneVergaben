<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

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
        return $this->belongsTo('App\Role');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions() {
        return $this->hasMany('App\Subscription');
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
     * @param \App\Subscription $subscription
     */
    public function sendSubscriptionVerificationNotification($subscription) {
        $this->notify(new Notifications\VerifySubscription($subscription));
    }
}

<?php

namespace App\Models;

use App\Http\Controllers\SubscriptionController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\URL;

/**
 * App\Models\User
 *
 * @property int $id
 * @property int $role_id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read String $cancel_all_subscriptions_url
 * @property-read string $first_name
 * @property-read string $initials
 * @property-read String $unsubscribe_all_url
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Role $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withVerifiedEmail()
 * @mixin \Eloquent
 */
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

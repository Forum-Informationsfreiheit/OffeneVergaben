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

    public function role() {
        return $this->belongsTo('App\Role');
    }

    public function getInitialsAttribute() {
        $nameArray = $this->name ? explode(' ',$this->name) : [];

        $initials = count($nameArray) > 0 ? $nameArray[0][0] : '';
        $initials.= count($nameArray) > 1 ? $nameArray[1][0] : '';

        return $initials != '' ? $initials : '??';
    }

    public function getFirstNameAttribute() {
        return $this->name ? explode(' ',$this->name)[0] : '';
    }

    public function isSuperAdmin() {
        return $this->id === 1;
    }

    public function isAdmin() {
        return $this->role_id === Role::ADMIN;
    }
}

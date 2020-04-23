<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    const REGISTERED = 100;

    const SUBSCRIBER = 200;

    const EDITOR     = 500;

    const ADMIN      = 900;

    /**
     * DEFAULT ROLE FOR NEW USERS
     */
    const DEFAULT_ROLE = self::REGISTERED;

    /**
     * Define the minimum required role for admin area access
     */
    const MIN_ROLE_FOR_ADMIN_AREA_ACCESS = self::EDITOR;
}

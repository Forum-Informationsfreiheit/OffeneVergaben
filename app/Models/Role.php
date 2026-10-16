<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @mixin \Eloquent
 */
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

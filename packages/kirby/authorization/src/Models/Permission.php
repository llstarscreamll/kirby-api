<?php

namespace Kirby\Authorization\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Class Permission.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string $display_name
 * @property string $description
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Permission extends SpatiePermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
    ];
}

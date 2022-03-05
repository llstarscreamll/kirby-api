<?php

namespace Kirby\Authorization\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Class Role.
 *
 * @property int                                                                    $id
 * @property string                                                                 $name
 * @property string                                                                 $guard_name
 * @property string                                                                 $display_name
 * @property string                                                                 $description
 * @property \Illuminate\Support\Collection<\Kirby\Authorization\Models\Permission> $permissions
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Role extends SpatieRole
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

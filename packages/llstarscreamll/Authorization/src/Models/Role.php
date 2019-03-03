<?php
namespace llstarscreamll\Authorization\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Class Role.
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

<?php

namespace Kirby\Machines\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'cost_center_id',
        'code',
        'name',
    ];
}

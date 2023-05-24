<?php

namespace Kirby\TruckScale\Models;

use Illuminate\Database\Eloquent\Model;

class Weighing extends Model
{
    protected $fillable = [
        'vehicle_plate', 'vehicle_type', 'driver_dni_number', 'driver_name'
    ];
}

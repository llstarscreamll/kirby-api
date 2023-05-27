<?php

namespace Kirby\TruckScale\Models;

use Illuminate\Database\Eloquent\Model;

class Weighing extends Model
{
    protected $fillable = [
        'weighing_type',
        'vehicle_plate',
        'vehicle_type',
        'driver_dni_number',
        'driver_name',
        'tare_weight',
        'gross_weight',
        'weighing_description',
        'status',
    ];
}

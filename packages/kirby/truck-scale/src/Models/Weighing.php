<?php

namespace Kirby\TruckScale\Models;

use Illuminate\Database\Eloquent\Model;
use Kirby\Users\Models\User;

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
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id', 'id');
    }
}

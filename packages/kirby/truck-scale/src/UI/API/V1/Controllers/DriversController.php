<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriversController
{
    public function index(Request $request)
    {
        return DB::table('weighings')
            ->when($request->s, fn ($q, $s) => $q->where('driver_dni_number', 'like', "%{$s}%"))
            ->orderBy('driver_dni_number', 'ASC')
            ->simplePaginate(10, [
                DB::raw('DISTINCT driver_dni_number AS id'),
                'driver_name AS name',
            ]);
    }
}

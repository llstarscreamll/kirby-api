<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiclesController
{
    public function index(Request $request)
    {
        $paginated = DB::table('weighings')
            ->when($request->s, fn ($q, $s) => $q->where('vehicle_plate', 'like', "%{$s}%"))
            ->groupBy('plate', 'type')
            ->orderBy('plate', 'ASC')
            ->simplePaginate(10, [
                'vehicle_plate AS plate',
                'vehicle_type AS type',
                DB::raw('GROUP_CONCAT(CONCAT(driver_dni_number, ",", driver_name) ORDER BY driver_dni_number DESC SEPARATOR "|") AS drivers')
            ]);

        return $paginated
            ->setCollection(
                $paginated
                    ->getCollection()
                    ->transform(fn($r) => tap($r, fn($r) => $r->drivers = array_map(fn ($d) => array_combine(['id', 'name'], explode(',', $d)), explode('|', $r->drivers))))
            );
    }
}

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
            ->where('created_at', '>', now()->subMonths(6)->toDateTimeString())
            ->groupBy('plate', 'type')
            ->orderBy('plate', 'ASC')
            ->simplePaginate(10, [
                'vehicle_plate AS plate',
                'vehicle_type AS type',
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(driver_dni_number, ",", driver_name) ORDER BY driver_dni_number DESC SEPARATOR "|") AS drivers'),
                DB::raw('GROUP_CONCAT(DISTINCT client ORDER BY client ASC SEPARATOR "|") AS clients'),
                DB::raw('GROUP_CONCAT(DISTINCT commodity ORDER BY commodity ASC SEPARATOR "|") AS commodities'),
            ]);

        return $paginated
            ->setCollection(
                $paginated
                    ->getCollection()
                    ->transform(fn ($r) => tap($r, fn ($r) => $r->drivers = array_map(fn ($d) => array_combine(['id', 'name'], explode(',', $d)), explode('|', $r->drivers))))
                    ->transform(fn ($r) => tap($r, fn ($r) => $r->clients = array_map(fn ($v) => ['name' => $v], explode('|', $r->clients))))
                    ->transform(fn ($r) => tap($r, fn ($r) => $r->commodities = array_map(fn ($v) => ['name' => $v], explode('|', $r->commodities))))
            );
    }
}

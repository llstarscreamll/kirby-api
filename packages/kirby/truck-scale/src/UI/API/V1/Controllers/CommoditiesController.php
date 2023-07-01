<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommoditiesController
{
    public function index(Request $request)
    {
        return DB::table('weighings')
            ->when($request->s, fn ($q, $s) => $q->where('commodity', 'like', "%{$s}%"))
            ->orderBy('commodity', 'ASC')
            ->simplePaginate(10, [DB::raw('DISTINCT commodity AS name')]);
    }
}

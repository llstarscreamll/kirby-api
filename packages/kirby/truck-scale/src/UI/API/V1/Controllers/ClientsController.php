<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientsController
{
    public function index(Request $request)
    {
        return DB::table('weighings')
            ->when($request->s, fn ($q, $s) => $q->where('client', 'like', "%{$s}%"))
            ->orderBy('client', 'ASC')
            ->simplePaginate(10, [DB::raw('DISTINCT client AS name')]);
    }
}

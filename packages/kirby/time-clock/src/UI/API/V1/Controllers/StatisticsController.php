<?php

namespace Kirby\TimeClock\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class StatisticsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class StatisticsController
{
    public function __invoke(Request $request) {
        if (! $request->user()->can('time-clock-logs.global-search')) {
            return abort(403);
        }

        return response()->json([
            'data' => [
                'people_inside_count' => DB::table('time_clock_logs')->whereNull('checked_out_at')->count('id')
            ]
        ]);
    }
}

<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Support\Arr;
use Kirby\TruckScale\Jobs\ExportWeighingsJob;
use Kirby\TruckScale\UI\API\V1\Requests\ExportWeighingsRequest;

class ExportWeighingsController
{
    public function __invoke(ExportWeighingsRequest $request)
    {
        ExportWeighingsJob::dispatch(Arr::get($request->validated(), 'filter', []), $request->user()->id);

        return ['data' => 'ok'];
    }
}

<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Illuminate\Support\Str;
use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Models\Weighing;
use Kirby\TruckScale\UI\API\V1\Requests\CancelWeighingRequest;

class CancelWeighingController
{
    public function __invoke(CancelWeighingRequest $request, $id)
    {
        Weighing::where('id', $id)
            ->where('status', '!=', WeighingStatus::Canceled)
            ->update([
                'status' => WeighingStatus::Canceled,
                'cancel_comment' => Str::of($request->input('comment'))->replaceMatches('/\t|\n/', '')->replaceMatches('/  +/', ' '),
            ]);

        return ['data' => 'ok'];
    }
}

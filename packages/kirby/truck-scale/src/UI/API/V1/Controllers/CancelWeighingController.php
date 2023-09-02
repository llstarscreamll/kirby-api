<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

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
                'cancel_comment' => $request->input('comment'),
            ]);

        return ['data' => 'ok'];
    }
}

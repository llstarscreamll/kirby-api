<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Models\Weighing;
use Kirby\TruckScale\UI\API\V1\Requests\ManualFinishWeighingRequest;

class ManualFinishWeighingController
{
    public function __invoke(ManualFinishWeighingRequest $_, $id)
    {
        Weighing::where(['id' => $id, 'status' => WeighingStatus::InProgress])
            ->update(['status' => WeighingStatus::ManualFinished]);

        return ['data' => 'ok'];
    }
}

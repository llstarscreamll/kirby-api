<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Enums\WeighingType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\TruckScale\UI\API\V1\Requests\CreateWeighingRequest;

class WeighingsController
{
    public function store(CreateWeighingRequest $request)
    {
        $data = [
            'weighing_type' => $request->weighing_type,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_type' => $request->vehicle_type,
            'driver_dni_number' => $request->driver_dni_number,
            'driver_name' => $request->driver_name,
            'tare_weight' => $request->weighing_type === WeighingType::Load ? $request->tare_weight : 0,
            'gross_weight' => in_array($request->weighing_type, [WeighingType::Unload, WeighingType::Weighing]) ? $request->gross_weight : 0,
            'weighing_description' => $request->weighing_description ?? '',
            'status' => WeighingType::Weighing === $request->weighing_type ? WeighingStatus::Finished : WeighingStatus::InProgress,
        ];

        return response()->json(['data' => Weighing::create($data)->id], 201);
    }
}

<?php

namespace Kirby\TruckScale\UI\API\V1\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Enums\WeighingType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\TruckScale\UI\API\V1\Requests\CreateWeighingRequest;
use Kirby\TruckScale\UI\API\V1\Requests\UpdateWeighingRequest;

class WeighingsController
{
    public function index(Request $request)
    {
        return Weighing::query()
            ->with(['createdBy:id,first_name,last_name', 'updatedBy:id,first_name,last_name'])
            ->when($request->input('filter.id'), fn ($q, $v) => $q->where('id', $v))
            ->when($request->input('filter.vehicle_plate'), fn ($q, $v) => $q->where('vehicle_plate', $v))
            ->when($request->input('filter.vehicle_type'), fn ($q, $v) => $q->where('vehicle_type', $v))
            ->when($request->input('filter.status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('filter.date'), fn ($q, $v) => $q->whereBetween('created_at', [Carbon::parse($v)->startOfDay(), Carbon::parse($v)->endOfDay()]))
            ->orderBy('id', 'desc')
            ->simplePaginate(10);
    }

    public function store(CreateWeighingRequest $request)
    {
        $data = [
            'weighing_type' => $request->weighing_type,
            'vehicle_plate' => Str::of($request->vehicle_plate)->upper(),
            'vehicle_type' => $request->vehicle_type,
            'driver_dni_number' => $request->driver_dni_number,
            'driver_name' => Str::of($request->driver_name)->upper()->replaceMatches('/\t|\n/', '')->replaceMatches('/  +/', ' '),
            'client' => Str::of($request->client)->upper()->replaceMatches('/\t|\n/', '')->replaceMatches('/  +/', ' '),
            'commodity' => Str::of($request->commodity)->upper()->replaceMatches('/\t|\n/', '')->replaceMatches('/  +/', ' '),
            'destination' => Str::of($request->destination)->replaceMatches('/\t|\n/', '')->replaceMatches('/  +/', ' '),
            'tare_weight' => WeighingType::Load === $request->weighing_type ? $request->tare_weight : 0,
            'gross_weight' => in_array($request->weighing_type, [WeighingType::Unload, WeighingType::Weighing]) ? $request->gross_weight : 0,
            'weighing_description' => Str::of($request->weighing_description ?? '')->replaceMatches('/\n+/', "\n"),
            'status' => WeighingType::Weighing === $request->weighing_type ? WeighingStatus::Finished : WeighingStatus::InProgress,
            'created_by_id' => $request->user()->id,
        ];

        return response()->json(['data' => Weighing::create($data)->id], 201);
    }

    public function show(string $id)
    {
        return response()->json(['data' => Weighing::with([
            'createdBy:id,first_name,last_name', 'updatedBy:id,first_name,last_name',
        ])->findOrFail($id)]);
    }

    public function update(UpdateWeighingRequest $request, string $ID)
    {
        $record = Weighing::find($ID);

        $fieldToUpdate = $request->weighing_type == WeighingType::Load ? 'gross_weight' : 'tare_weight';
        $record->fill([$fieldToUpdate => $request->input($fieldToUpdate)]);

        if ($record->status === WeighingStatus::Finished) {
            return response()->json(['errors' => ['status' => ['No se permite actualizaciones a registros finalizados']]], 422);
        }

        if ($record->tare_weight > $record->gross_weight) {
            return response()->json(['errors' => [$fieldToUpdate => ['Peso tara no puede ser mayor que peso bruto']]], 422);
        }

        $record->fill([
            'status' => WeighingStatus::Finished,
            'updated_by_id' => $request->user()->id,
        ]);

        $record->save();

        return [];
    }
}

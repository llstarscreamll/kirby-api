<?php

namespace Kirby\TruckScale\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Enums\WeighingStatus;

class ExportWeighingsRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('truck-scale.export');
    }

    public function rules()
    {
        return [
            'filter.id' => ['nullable', 'numeric', 'min:1'],
            'filter.vehicle_plate' => ['nullable', 'string', 'min:4', 'max:8'],
            'filter.vehicle_type' => ['nullable', 'string', 'in:'.implode(',', VehicleType::getValues())],
            'filter.status' => ['nullable', 'string', 'in:'.implode(',', WeighingStatus::getValues())],
            'filter.date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}

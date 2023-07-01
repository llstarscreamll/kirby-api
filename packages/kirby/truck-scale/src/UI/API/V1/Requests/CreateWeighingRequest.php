<?php

namespace Kirby\TruckScale\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Enums\WeighingType;

class CreateWeighingRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('truck-scale.create');
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'weighing_type' => ['required', 'string', 'in:'.implode(',', WeighingType::getValues())],
            'vehicle_plate' => ['required', 'string', 'regex:/^[a-zA-Z]{1,3}[0-9]{3,5}$/'],
            'vehicle_type' => ['required', 'string', 'in:'.implode(',', VehicleType::getValues())],
            'driver_dni_number' => ['required', 'numeric', 'min:1'],
            'driver_name' => ['required', 'string', 'max:255'],
            'client' => ['required', 'string', 'max:255'],
            'commodity' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'tare_weight' => ['required_if:weighing_type,'.WeighingType::Load, 'nullable', 'numeric', 'min:1'],
            'gross_weight' => ['required_if:weighing_type,'.WeighingType::Unload.','.WeighingType::Weighing, 'nullable', 'numeric', 'min:1'],
            'weighing_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

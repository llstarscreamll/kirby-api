<?php

namespace Kirby\TruckScale\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\TruckScale\Enums\WeighingType;

class UpdateWeighingRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('truck-scale.update');
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'weighing_type' => ['required', 'string', 'in:'.WeighingType::Load.','.WeighingType::Unload],
            'tare_weight' => ['required_if:weighing_type,'.WeighingType::Unload.','.WeighingType::Weighing, 'nullable', 'numeric'],
            'gross_weight' => ['required_if:weighing_type,'.WeighingType::Load, 'nullable', 'numeric'],
            'weighing_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'weighing_type.in' => 'Solo se permite actualizaciones a registros de tipo cargue y descargue'
        ];
    }
}

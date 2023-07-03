<?php

namespace Kirby\TruckScale\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeighingSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('truck-scale.update-settings');
    }

    public function rules()
    {
        return [];
    }
}

<?php

namespace Kirby\TruckScale\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualFinishWeighingRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('truck-scale.manual-finish');
    }

    public function rules()
    {
        return [];
    }
}

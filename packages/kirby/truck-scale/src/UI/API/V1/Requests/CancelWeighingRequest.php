<?php

namespace Kirby\TruckScale\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelWeighingRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('truck-scale.cancel');
    }

    public function rules()
    {
        return [
            'comment' => ['required', 'min:5', 'max:255'],
        ];
    }
}

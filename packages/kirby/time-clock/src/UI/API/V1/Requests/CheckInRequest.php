<?php

namespace Kirby\TimeClock\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

/**
 * Class CheckInRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('time-clock-logs.check-in');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'identification_code' => ['required', 'exists:identifications,code'],
            'novelty_type_id' => ['nullable', 'numeric', 'exists:novelty_types,id'],
            'work_shift_id' => ['nullable', 'numeric'],
            'sub_cost_center_id' => ['nullable', 'numeric', 'exists:sub_cost_centers,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return Arr::dot(trans('time-clock::validation.custom'));
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return trans('time-clock::validation.attributes');
    }
}

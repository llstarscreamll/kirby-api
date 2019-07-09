<?php

namespace llstarscreamll\TimeClock\UI\API\Requests;

use Illuminate\Support\Arr;
use Illuminate\Foundation\Http\FormRequest;

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
        return true;
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
            'novelty_type.id' => ['exists:novelty_types,id'],
            'work_shift_id' => ['exists:work_shifts,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return Arr::dot(trans('time-clock::validation.custom'));
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return trans('time-clock::validation.attributes');
    }
}

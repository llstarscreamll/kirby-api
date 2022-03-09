<?php

namespace Kirby\Machines\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMachinesRequest extends FormRequest
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
            'filter' => ['nullable', 'array'],
            'filter.search' => ['string'],
            'filter.short_name' => ['string'],
            'filter.cost_center_ids' => ['array'],
            'filter.cost_center_ids.*' => ['int', 'min:1'],
        ];
    }
}

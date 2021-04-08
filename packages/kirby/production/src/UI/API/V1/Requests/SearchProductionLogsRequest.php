<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductionLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('production-logs.search');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            's' => ['nullable', 'string'],
        ];
    }
}

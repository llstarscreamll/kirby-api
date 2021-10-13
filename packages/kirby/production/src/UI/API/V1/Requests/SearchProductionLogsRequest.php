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
        return $this->user()->hasAnyPermission(['production-logs.search', 'production-logs.export-to-csv']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'creation_date' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'machine_id' => ['nullable', 'integer', 'min:1'],
            'net_weight' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

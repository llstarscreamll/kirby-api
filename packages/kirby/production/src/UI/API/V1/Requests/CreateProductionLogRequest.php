<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductionLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('production-logs.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'customer_id' => ['integer', 'exists:customers,id'],
            'batch' => ['integer'],
            'tare_weight' => ['required', 'numeric'],
            'gross_weight' => ['required', 'numeric', 'gt:tare_weight'],
        ];
    }
}

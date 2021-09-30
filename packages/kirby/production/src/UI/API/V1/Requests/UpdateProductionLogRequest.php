<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Production\Enums\Tag;

class UpdateProductionLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('production-logs.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'tag' => ['nullable', 'in:' . implode(',', Tag::getValues())],
            'batch' => ['nullable', 'integer'],
        ];
    }
}

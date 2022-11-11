<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kirby\Production\Enums\Purpose;
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
            'employee_code' => ['required', 'string', Rule::exists('identifications', 'code')->where('type', 'uuid')],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'purpose' => ['required', 'in:'.implode(',', Purpose::getValues())],
            'tag' => ['required', 'in:'.implode(',', Tag::getValues())],
            'batch' => ['nullable', 'integer'],
            'tare_weight' => ['nullable', 'numeric'],
            'gross_weight' => ['nullable', 'numeric', 'gt:tare_weight'],
        ];
    }
}

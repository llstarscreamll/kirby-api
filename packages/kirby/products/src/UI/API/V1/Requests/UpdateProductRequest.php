<?php

namespace Kirby\Products\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @internal
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('products.update');
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'short_name' => ['required', 'string', 'min:3', 'max:255'],
            'internal_code' => ['required', 'string', 'min:2', 'max:255', Rule::unique('products')->ignore(
                $this->route()->parameter('product')
            )],
            'customer_code' => ['required', 'string', 'min:2', 'max:255', Rule::unique('products')->ignore(
                $this->route()->parameter('product')
            )],
            'wire_gauge_in_bwg' => ['nullable', 'string', 'min:3', 'max:100'],
            'wire_gauge_in_mm' => ['required', 'numeric', 'min:0'],
        ];
    }
}

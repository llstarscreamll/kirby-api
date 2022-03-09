<?php

namespace Kirby\Products\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @internal
 */
class CreateProductRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('products.create');
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'short_name' => ['required', 'string', 'min:3', 'max:255'],
            'internal_code' => ['required', 'string', 'min:2', 'max:255', 'unique:products'],
            'customer_code' => ['required', 'string', 'min:2', 'max:255', 'unique:products'],
            'wire_gauge_in_bwg' => ['nullable', 'string', 'min:3', 'max:100'],
            'wire_gauge_in_mm' => ['required', 'numeric', 'min:0'],
        ];
    }
}

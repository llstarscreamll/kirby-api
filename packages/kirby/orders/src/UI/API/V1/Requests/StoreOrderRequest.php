<?php

namespace Kirby\Orders\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'products.*.product.id' => ['required', 'numeric', 'min:1', Rule::exists('products')->where('active', true)],
            'products.*.requested_quantity' => ['required', 'numeric', 'min:1'],
            'shipping.address_street_type' => ['required', 'string'],
            'shipping.address_line_1' => ['required', 'string'],
            'shipping.address_line_2' => ['required', 'string'],
            'shipping.address_line_3' => ['required', 'numeric'],
            'shipping.address_additional_info' => ['nullable', 'string'],
            'payment_method.name' => ['required', 'string', 'in:cash'],
        ];
    }
}

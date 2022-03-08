<?php

namespace Kirby\Products\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @internal
 */
class GetProductRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('products.details');
    }

    public function rules()
    {
        return [];
    }
}

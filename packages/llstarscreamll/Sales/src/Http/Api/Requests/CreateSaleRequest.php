<?php
namespace llstarscreamll\Sales\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateSaleRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSaleRequest extends FormRequest
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
            'shipping_to_id'   => ['int'],
            'customer_id'      => ['int'],
            'stockroom_id'     => ['required', 'int', 'exists:stockrooms,id'],
            'items.*.id'       => ['required', 'int', 'exists:items'],
            'items.*.quantity' => ['required', 'int'],
        ];
    }
}

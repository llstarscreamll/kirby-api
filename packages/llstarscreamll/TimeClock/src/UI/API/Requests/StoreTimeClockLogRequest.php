<?php
namespace llstarscreamll\TimeClock\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreTimeClockLogRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class StoreTimeClockLogRequest extends FormRequest
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
            'action' => ['in:check_in,check_out'],
            'identification_code' => ['exists:identifications,code'],
        ];
    }
}

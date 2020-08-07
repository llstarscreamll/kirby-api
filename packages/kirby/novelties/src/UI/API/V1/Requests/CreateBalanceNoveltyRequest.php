<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateBalanceNoveltyRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateBalanceNoveltyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.create-balance-novelty');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['required', 'numeric', 'exists:employees,id'],
            'time' => ['required', 'numeric'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }
}

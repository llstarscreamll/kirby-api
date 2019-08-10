<?php

namespace llstarscreamll\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateNoveltyRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateNoveltyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => ['numeric', 'min:1'],
            'employee_id' => ['numeric', 'min:1'],
            'novelty_type_id' => ['numeric', 'min:1'],
            'total_time_in_minutes' => ['numeric'],
        ];
    }
}

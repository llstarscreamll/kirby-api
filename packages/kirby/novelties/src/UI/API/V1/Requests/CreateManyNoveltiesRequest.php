<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateManyNoveltiesRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.create-many');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_ids.*' => ['numeric'],
            'employee_ids' => ['required', 'array', 'exists:employees,id'],
            'novelties' => ['required', 'array'],
            'novelties.*.novelty_type_id' => ['required', 'numeric', 'exists:novelty_types,id'],
            'novelties.*.start_at' => ['required', 'date'],
            'novelties.*.end_at' => ['required', 'date', 'after:novelties.*.start_at'],
            'novelties.*.comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}

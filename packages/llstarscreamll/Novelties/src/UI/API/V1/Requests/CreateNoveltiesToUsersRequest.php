<?php

namespace llstarscreamll\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateNoveltiesToUsersRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesToUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.create-novelties-to-users');
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
            'novelties.*.scheduled_start_at' => ['required', 'date'],
            'novelties.*.scheduled_end_at' => ['required', 'date', 'after:novelties.*.scheduled_start_at'],
            'novelties.*.comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}

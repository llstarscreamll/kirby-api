<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

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
            'employee_id' => ['required', 'numeric', 'min:1'],
            'novelty_type_id' => ['required', 'numeric', 'min:1'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date', 'after:scheduled_start_at', 'required_with:scheduled_start_at'],
            'total_time_in_minutes' => ['nullable', 'numeric'],
            'comment' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SearchNoveltiesRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchNoveltiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.search');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'search' => ['nullable', 'string'],
            'employees.*.id' => ['nullable', 'numeric'],
            'novelty_types.*.id' => ['nullable', 'numeric'],
            'cost_centers.*.id' => ['nullable', 'numeric'],
            'start_at.from' => ['nullable', 'date'],
            'start_at.to' => ['nullable', 'date', 'required_with:start_at.from', 'after:start_at.from'],
            'time_clock_log_check_out_start_date' => ['nullable', 'date', 'required_with:end_date'],
            'time_clock_log_check_out_end_date' => ['nullable', 'date', 'required_with:start_date', 'after:start_date'],
        ];
    }
}

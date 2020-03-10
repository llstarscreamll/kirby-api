<?php

namespace Kirby\TimeClock\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

/**
 * Class CreateTimeClockLogRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('time-clock-logs.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['nullable', 'numeric', 'exists:employees,id'],
            'sub_cost_center_id' => ['nullable', 'numeric', 'exists:sub_cost_centers,id'],
            'work_shift_id' => ['nullable', 'numeric'],
            'checked_in_at' => ['nullable', 'date'],
            'check_in_novelty_type_id' => ['nullable', 'numeric', 'exists:novelty_types,id'],
            'check_in_sub_cost_center_id' => ['nullable', 'numeric', 'exists:sub_cost_centers,id'],
            'checked_out_at' => ['nullable', 'date'],
            'check_out_novelty_type_id' => ['nullable', 'numeric', 'exists:novelty_types,id'],
            'check_out_sub_cost_center_id' => ['nullable', 'numeric', 'exists:sub_cost_centers,id'],
            'checked_in_by_id' => ['nullable', 'numeric', 'exists:users,id'],
            'checked_out_by_id' => ['nullable', 'numeric', 'exists:users,id'],
        ];
    }
}

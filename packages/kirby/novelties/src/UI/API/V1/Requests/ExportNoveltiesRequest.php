<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ExportNoveltiesRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportNoveltiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.export');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['nullable', 'numeric'],
            'time_clock_log_check_out_start_date' => ['required', 'date'],
            'time_clock_log_check_out_end_date' => ['required', 'date', 'after:time_clock_log_check_out_start_date'],
        ];
    }
}

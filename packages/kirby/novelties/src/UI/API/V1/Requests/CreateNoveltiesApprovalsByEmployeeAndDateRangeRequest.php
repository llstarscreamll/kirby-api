<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateNoveltiesApprovalsByEmployeeAndDateRangeRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesApprovalsByEmployeeAndDateRangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.create-approvals-by-employee-and-date-range');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['numeric', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}

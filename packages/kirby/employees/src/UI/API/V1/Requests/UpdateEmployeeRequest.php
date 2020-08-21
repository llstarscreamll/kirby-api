<?php

namespace Kirby\Employees\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateEmployeeRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('employees.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'code' => ['required', 'string'],
            'identification_number' => ['required', 'string'],
            'location' => ['required', 'string'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'regex:/\+\d{12}$/', Rule::unique('users', 'phone_number')->ignore((int) $this->route()->parameter('employee'))],
            'position' => ['required', 'string'],
            'salary' => ['required', 'numeric'],
            'cost_center.id' => ['required', 'numeric'],
            'work_shifts.*.id' => ['required', 'numeric'],
            'identifications.*.name' => ['required', 'string', 'max: 255'],
            'identifications.*.code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('identifications')->ignore(
                    (int) $this->route()->parameter('employee'),
                    'employee_id'
                ),
            ],
        ];
    }
}

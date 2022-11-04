<?php

namespace Kirby\Employees\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateEmployeeRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('employees.create');
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
            'email' => ['required', 'string', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'roles' => ['nullable', 'array'],
            'roles.*.id' => ['nullable', 'int', 'min:1'],
            'code' => ['required', 'string', 'unique:employees'],
            'identification_number' => ['required', 'string', 'unique:employees'],
            'location' => ['required', 'string'],
            'address' => ['required', 'string'],
            'phone_prefix' => ['required', 'regex:/\+\d{1,3}$/'],
            'phone' => ['required', 'regex:/\d{10}$/', 'unique:users,phone_number'],
            'position' => ['required', 'string'],
            'salary' => ['required', 'numeric'],
            'generate_token' => ['nullable', 'in:15d,30d,120d,180d'],
            'cost_center.id' => ['required', 'numeric'],
            'work_shifts.*.id' => ['required', 'numeric'],
            'identifications.*.name' => ['required', 'string', 'max: 255'],
            'identifications.*.code' => [
                'required',
                'string',
                'max:255',
                'unique:identifications',
            ],
        ];
    }
}

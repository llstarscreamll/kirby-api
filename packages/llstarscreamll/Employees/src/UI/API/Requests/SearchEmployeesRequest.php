<?php

namespace llstarscreamll\Employees\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SearchEmployeesRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchEmployeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('employees.search');
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
        ];
    }
}

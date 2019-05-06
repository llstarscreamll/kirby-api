<?php

namespace llstarscreamll\Employees\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SyncEmployeesByCsvFileRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncEmployeesByCsvFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ];
    }
}

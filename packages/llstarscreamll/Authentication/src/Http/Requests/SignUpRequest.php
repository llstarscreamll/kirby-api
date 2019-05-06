<?php

namespace llstarscreamll\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SignUpRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SignUpRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed'],
        ];
    }
}

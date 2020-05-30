<?php

namespace Kirby\Authentication\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class LoginRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LoginRequest extends FormRequest
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
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ];
    }
}

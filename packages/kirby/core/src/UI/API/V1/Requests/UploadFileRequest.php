<?php

namespace Kirby\Core\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Class UploadFileRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UploadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => ['required', 'file', 'max:256000', 'mimetypes:application/pdf,image/jpeg,image/png'],
        ];
    }
}

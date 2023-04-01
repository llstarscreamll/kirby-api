<?php

namespace Kirby\Core\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DownloadFileRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DownloadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->hasAnyPermission(['novelties.get', 'novelties.global-search', 'novelties.employee-search']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}

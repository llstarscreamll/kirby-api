<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ListNoveltiesSettingsRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ListNoveltiesSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.settings.show');
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

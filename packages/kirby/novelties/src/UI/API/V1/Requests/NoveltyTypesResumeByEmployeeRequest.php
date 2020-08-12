<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Core\Rules\IsoDateTimeRule;

/**
 * Class NoveltyTypesResumeByEmployeeRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyTypesResumeByEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.resume-by-novelty-type-and-employee');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_date' => ['required', new IsoDateTimeRule()],
            'end_date' => ['required', new IsoDateTimeRule(), 'after:start_date'],
        ];
    }
}

<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Core\Rules\IsoDateTimeRule;

/**
 * Class ExportEmployeeResumeByNoveltyTypesRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportEmployeeResumeByNoveltyTypesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.export-resume-by-novelty-type');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['nullable', 'numeric'],
            'start_at' => ['required', new IsoDateTimeRule()],
            'end_at' => ['required', new IsoDateTimeRule(), 'after:start_at'],
        ];
    }
}

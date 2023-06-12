<?php

namespace Kirby\TimeClock\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ExportTimeClockLogsRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportTimeClockLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('time-clock-logs.global-search');
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
            'checkedInStart' => ['required', 'date'],
            'checkedInEnd' => ['required', 'date', 'after:checkedInStart'],
            'peopleInsideOnly' => ['nullable', 'boolean'],
        ];
    }
}

<?php

namespace Kirby\TimeClock\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SearchTimeClockLogsRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchTimeClockLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->hasAnyPermission(['time-clock-logs.global-search', 'time-clock-logs.employee-search']);
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
            'checkedInStart' => ['nullable', 'date'],
            'checkedInEnd' => ['nullable', 'date', 'after:checkedInStart'],
            'peopleInsideOnly' => ['nullable', 'boolean'],
        ];
    }
}

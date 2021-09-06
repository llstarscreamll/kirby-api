<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Core\Rules\IsoDateTimeRule;

class ExportProductionLogsToCsvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('production-logs.export-to-csv');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => ['nullable', new IsoDateTimeRule()],
            'to' => ['nullable', new IsoDateTimeRule(), 'after:start_at'],
        ];
    }
}

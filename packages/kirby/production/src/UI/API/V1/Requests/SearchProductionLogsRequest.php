<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Core\Rules\IsoDateTimeRule;

class SearchProductionLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->hasAnyPermission(['production-logs.search', 'production-logs.export-to-csv']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'filter.employee_id' => ['nullable', 'integer', 'min:1'],
            'filter.product_id' => ['nullable', 'integer', 'min:1'],
            'filter.machine_id' => ['nullable', 'integer', 'min:1'],
            'filter.net_weight' => ['nullable', 'numeric', 'min:0'],
            'filter.tag_updated_at.start' => ['nullable', new IsoDateTimeRule(), 'required_with:filter.tag_updated_at.end'],
            'filter.tag_updated_at.end' => ['nullable', new IsoDateTimeRule(), 'required_with:filter.tag_updated_at.start', 'after:filter.tag_updated_at.start'],
        ];
    }
}

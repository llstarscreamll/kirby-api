<?php

namespace Kirby\Production\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Core\Rules\IsoDateTimeRule;
use Kirby\Production\Enums\Tag;

class SearchProductionLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->hasAnyPermission(['production-logs.search', 'production-logs.export-to-csv', 'production.reports']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'filter.employee_ids' => ['nullable', 'array'],
            'filter.employee_ids.*' => ['integer', 'min:1'],
            'filter.product_ids' => ['nullable', 'array'],
            'filter.product_ids.*' => ['integer', 'min:1'],
            'filter.machine_ids' => ['nullable', 'array'],
            'filter.machine_ids.*' => ['integer', 'min:1'],
            'filter.sub_cost_center_ids' => ['nullable', 'array'],
            'filter.sub_cost_center_ids.*' => ['integer', 'min:1'],
            'filter.net_weight' => ['nullable', 'numeric', 'min:0'],
            'filter.tags' => ['nullable', 'array'],
            'filter.tags.*' => ['string', 'in:'.implode(',', Tag::getValues())],
            'filter.tag_updated_at.start' => ['nullable', new IsoDateTimeRule(), 'required_with:filter.tag_updated_at.end'],
            'filter.tag_updated_at.end' => ['nullable', new IsoDateTimeRule(), 'required_with:filter.tag_updated_at.start', 'after:filter.tag_updated_at.start'],
        ];
    }
}

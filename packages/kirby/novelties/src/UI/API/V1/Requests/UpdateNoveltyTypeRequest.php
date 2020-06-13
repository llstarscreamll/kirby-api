<?php

namespace Kirby\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;

/**
 * Class UpdateNoveltyTypeRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateNoveltyTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelty-types.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => ['required', 'string', 'unique:novelty_types,code,'.$this->route('novelty_type')],
            'name' => ['required', 'string'],
            'context_type' => ['nullable', 'string'],
            'time_zone' => ['required', 'string'],
            'apply_on_days_of_type' => ['nullable', 'in:'.implode(',', DayType::getValues())],
            'apply_on_time_slots' => ['nullable', 'array'],
            'operator' => ['required', 'string', 'in:'.implode(',', NoveltyTypeOperator::getValues())],
            'requires_comment' => ['required', 'boolean'],
            'keep_in_report' => ['required', 'boolean'],
        ];
    }
}

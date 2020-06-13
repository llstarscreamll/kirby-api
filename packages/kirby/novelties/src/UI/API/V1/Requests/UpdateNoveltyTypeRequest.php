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
            'code' => ['string'],
            'name' => ['string'],
            'context_type' => ['nullable', 'string'],
            'time_zone' => ['string'],
            'apply_on_days_of_type' => ['in:'.implode(',', DayType::getValues())],
            'apply_on_time_slots' => ['array'],
            'operator' => ['string', 'in:'.implode(',', NoveltyTypeOperator::getValues())],
            'requires_comment' => ['boolean'],
            'keep_in_report' => ['boolean'],
        ];
    }
}

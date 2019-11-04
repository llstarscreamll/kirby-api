<?php

namespace Kirby\WorkShifts\UI\API\Requests;

use Kirby\Core\Abstracts\FormRequestAbstract;

/**
 * Class CreateWorkShiftRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateWorkShiftRequest extends FormRequestAbstract
{
    /**
     * User must have ANY of this roles OR permissions to make this request.
     *
     * @var array
     */
    protected $access = [
        'roles' => [],
        'permissions' => ['work-shift.create'],
    ];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->hasAccess();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:50'],
            'grace_minutes_before_start_times' => ['numeric', 'min:0'],
            'grace_minutes_after_start_times' => ['numeric', 'min:0'],
            'grace_minutes_before_end_times' => ['numeric', 'min:0'],
            'grace_minutes_after_end_times' => ['numeric', 'min:0'],
            'meal_time_in_minutes' => ['numeric', 'min:0'],
            'min_minutes_required_to_discount_meal_time' => ['numeric', 'min:0'],
            'time_slots' => ['required', 'array'],
        ];
    }
}

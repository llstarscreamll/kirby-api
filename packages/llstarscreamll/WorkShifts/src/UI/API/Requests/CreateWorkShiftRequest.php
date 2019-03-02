<?php
namespace llstarscreamll\WorkShifts\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateWorkShiftRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateWorkShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'                                       => ['required', 'min:3', 'max:50'],
            'start_time'                                 => ['required'],
            'end_time'                                   => ['required'],
            'grace_minutes_for_start_time'               => ['numeric', 'min:0'],
            'grace_minutes_for_end_time'                 => ['numeric', 'min:0'],
            'meal_time_in_minutes'                       => ['numeric', 'min:0'],
            'min_minutes_required_to_discount_meal_time' => ['numeric', 'min:0'],
        ];
    }
}

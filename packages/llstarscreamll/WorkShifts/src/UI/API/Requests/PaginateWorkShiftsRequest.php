<?php
namespace llstarscreamll\WorkShifts\UI\API\Requests;

use llstarscreamll\Core\Abstracts\FormRequestAbstract;

/**
 * Class PaginateWorkShiftsRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PaginateWorkShiftsRequest extends FormRequestAbstract
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
            'filter.search' => ['nullable', 'string', 'min:2', 'max:100'],
        ];
    }
}

<?php

namespace Kirby\WorkShifts\UI\API\V1\Requests;

use Kirby\Core\Abstracts\FormRequestAbstract;

/**
 * Class PaginateWorkShiftsRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PaginateWorkShiftsRequest extends FormRequestAbstract
{
    /**
     * User must have ANY of this roles OR permissions to make this request.
     *
     * @var array
     */
    protected $access = [
        'roles' => [],
        'permissions' => ['work-shift.search'],
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->hasAccess();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'filter.search' => ['nullable', 'string', 'min:2', 'max:100'],
        ];
    }
}

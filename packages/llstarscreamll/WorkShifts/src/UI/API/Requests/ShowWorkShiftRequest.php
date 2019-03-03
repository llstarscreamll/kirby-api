<?php
namespace llstarscreamll\WorkShifts\UI\API\Requests;

use llstarscreamll\Core\Abstracts\FormRequestAbstract;

/**
 * Class ShowWorkShiftRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ShowWorkShiftRequest extends FormRequestAbstract
{
    /**
     * User must have ANY of this roles OR permissions to make this request.
     *
     * @var array
     */
    protected $access = [
        'roles'       => [],
        'permissions' => ['work-shift.view'],
    ];

    /**
     * @var array
     */
    protected $urlParameters = ['work_shift'];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
            'work_shift' => ['required', 'numeric'],
        ];
    }
}

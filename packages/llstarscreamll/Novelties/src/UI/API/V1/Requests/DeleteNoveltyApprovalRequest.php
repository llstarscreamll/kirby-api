<?php

namespace llstarscreamll\Novelties\UI\API\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DeleteNoveltyApprovalRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('novelties.approvals.delete');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}

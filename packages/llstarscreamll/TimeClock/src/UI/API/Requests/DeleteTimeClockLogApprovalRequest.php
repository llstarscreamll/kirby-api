<?php

namespace llstarscreamll\TimeClock\UI\API\Requests;

use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DeleteTimeClockLogApprovalRequest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteTimeClockLogApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('time-clock-logs.approvals.delete');
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

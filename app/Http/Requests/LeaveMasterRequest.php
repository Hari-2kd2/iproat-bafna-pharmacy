<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveMasterRequest extends FormRequest
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
    public function rules()
    {
        if (isset($this->leave_master_id)) {
            return [
                'leave_type_id'  => ['required', 'numeric', Rule::unique('leave_masters')->where('leave_type_id', $this->leave_type_id)->where('finger_print_id', $this->finger_print_id)->ignore($this->leave_master_id, 'leave_master_id')],
                'finger_print_id'  => ['required',  Rule::unique('leave_masters')->where('leave_type_id', $this->leave_type_id)->where('finger_print_id', $this->finger_print_id)->ignore($this->leave_master_id, 'leave_master_id')],
                'num_of_day' => 'required|numeric'
            ];
        }
        return [
            'leave_type_id'  => ['required', 'numeric', Rule::unique('leave_masters')->where('leave_type_id', $this->leave_type_id)->where('finger_print_id', $this->finger_print_id)],
            'finger_print_id'  => ['required',  Rule::unique('leave_masters')->where('leave_type_id', $this->leave_type_id)->where('finger_print_id', $this->finger_print_id)],
            'num_of_day' => 'required|numeric'
        ];
    }
}

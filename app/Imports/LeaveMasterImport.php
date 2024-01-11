<?php

namespace App\Imports;

use App\Model\Branch;
use App\Model\LeaveType;
use App\Model\LeaveMaster;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;


class LeaveMasterImport implements ToModel, WithValidation, WithStartRow
{
    use Importable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function rules(): array
    {
        return [
            '*.0' => 'required',
            '*.1' => 'required|exists:branch,branch_name',
            '*.2' => 'required|exists:department,department_name',
            '*.3' => 'required',
            '*.4' => 'required|regex:/^\S*$/u|exists:employee,finger_id',
            '*.5' => 'required|exists:leave_type,leave_type_name',
            '*.6' => 'required|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '0.required' => 'Sr.No is required',
            '1.required' => 'Branch name is required',
            '2.required' => 'Department is required',
            '3.required' => 'Employee Name is required',
            '4.required' => 'Employee ID is required',
            '4.exists' => 'Employee Id should exists in employee master ',
            '4.regex' => 'Space not allowed in Employee ID',
            '5.required' => 'Leave Type Name Field is Required ',
            '5.exists' => 'LeaveType name should exists in leave type master ',
            '6.required' => 'Leave Limit field is required',
        ];
    }

    public function model(array $row)
    {
        $leaveType = LeaveType::where('leave_type_name', $row[5])->first();
        $hasLeaveMaster = LeaveMaster::where('finger_print_id', $row[4])->where('leave_type_id', $leaveType->leave_type_id)->first();

        $array = [
            'finger_print_id' => $row[4],
            'leave_type_id' => $leaveType->leave_type_id,
            'num_of_day' => $row[6],
        ];

        if ($hasLeaveMaster) {
            $hasLeaveMaster->update($array);
        } else {
            LeaveMaster::create($array);
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}

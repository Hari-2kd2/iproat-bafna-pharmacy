<?php

namespace App\Http\Controllers\Leave;

use App\Exports\LeaveMasterExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\LeaveMasterRequest;
use App\Imports\LeaveMasterImport;
use App\Lib\Enumerations\UserStatus;
use App\Model\Branch;
use App\Model\Department;
use App\Model\Employee;
use App\Model\LeaveApplication;
use App\Model\LeaveMaster;
use App\Model\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class LeaveMasterController extends Controller
{

    public function index()
    {
        $results = Employee::where('status', UserStatus::$ACTIVE)->with(['leaveMaster' => function ($q) {
            $q->with('leaveType');
        }])->orderBy('finger_id')->get();

        // dd($results);
        return view('admin.leave.leaveMaster.index', ['results' => $results]);
    }

    public function create(Request $request)
    {
        $leaveTypeList = LeaveType::pluck('leave_type_name', 'leave_type_id');
        $leaveMasterLists = Employee::where('status', UserStatus::$ACTIVE)->with(['leaveMaster' => function ($q) {
            $q->with('leaveType');
        }])->get();
        return view('admin.leave.leaveMaster.form', ['leaveTypeList' => $leaveTypeList, 'leaveMasterLists' => $leaveMasterLists]);
    }

    public function store(LeaveMasterRequest $request)
    {
        $input = $request->all();
        try {
            LeaveMaster::create($input);
            $bug = 0;
        } catch (\Exception $e) {
            $bug = 1;
        }

        if ($bug == 0) {
            return redirect()->back()->with('success', 'Leave Master successfully saved.');
        } else {
            return redirect()->back()->with('error', 'Something Error Found !, Please try again.');
        }
    }

    public function edit($id)
    {
        $editModeData = LeaveMaster::findOrFail($id);
        $leaveTypeList = LeaveType::pluck('leave_type_name', 'leave_type_id');
        $employeeList = ['' => '---Please Select ---'];
        $leaveMasterLists = Employee::where('status', UserStatus::$ACTIVE)->with(['leaveMaster' => function ($q) {
            $q->with('leaveType');
        }])->get();

        return view('admin.leave.leaveMaster.form', ['editModeData' => $editModeData, 'leaveTypeList' => $leaveTypeList, 'leaveMasterLists' => $leaveMasterLists]);
    }

    public function update(LeaveMasterRequest $request, $id)
    {
        $data = LeaveMaster::findOrFail($id);
        $input = $request->all();
        try {
            $data->update($input);
            $bug = 0;
        } catch (\Exception $e) {
            $bug = 1;
        }

        if ($bug == 0) {
            return redirect('leaveMaster')->with('success', 'Leave Master successfully updated.');
        } else {
            return redirect('leaveMaster')->with('error', 'Something Error Found !, Please try again.');
        }
    }

    public function destroy($id)
    {

        $count = LeaveApplication::where('leave_type_id', '=', $id)->count();

        if ($count > 0) {
            return "hasForeignKey";
        }

        try {
            $data = LeaveMaster::findOrFail($id);
            $data->delete();
            $bug = 0;
        } catch (\Exception $e) {
            $bug = 1;
        }

        if ($bug == 0) {
            echo "success";
        } elseif ($bug == 1451) {
            echo 'hasForeignKey';
        } else {
            echo 'error';
        }
    }
    public function leaveMasterTemplate()
    {
        $file_name = 'templates/leave_master.xlsx';
        $file = Storage::disk('public')->get($file_name);
        return (new Response($file, 200))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function import(FileUploadRequest $request)
    {
        try {
            $file = $request->file('select_file');

            // $sheetToArray = (new LeaveMasterImport($request->all()))->toArray($file);
            // $employeeList = Employee::with('branch', 'department')->get();
            // $leaveTypeList = LeaveType::get();
            // // dd($sheetToArray[0]);
            // foreach ($sheetToArray[0] as $row => $array) {

            //     $validator = Validator::make($array, [
            //         '0' => 'required',
            //         '1' => 'required',
            //         '2' => 'required',
            //         '3' => 'required',
            //         '4' => 'required',
            //         '5' => 'required',
            //         '6' => 'required',
            //     ], [
            //         '0.required' => 'Sr.No is required',
            //         '1.required' => 'Branch name is required',
            //         '2.required' => 'Department is required',
            //         '3.required' => 'Employee Name is required',
            //         '4.required' => 'Employee ID is required',
            //         '5.required' => 'Leave Type Name Field is Required ',
            //         '6.required' => 'Leave Limit field is required',
            //     ]);

            //     if ($validator->fails()) {
            //         return redirect()->back()->with('error', $validator->getMessageBag()->first());
            //     }

            //     $employeeValidation = $employeeList->filter(function ($q) use ($array) {
            //         return $q->branch->branch_name == $array[1] && trim(trim($q->first_name) . ' ' . trim($q->last_name)) == $array[3]
            //             && $q->department->department_name == $array[2] &&  $q->finger_id == $array[4];
            //     })->values()->first();

            //     $leaveValidation = $leaveTypeList->filter(function ($q) use ($array) {
            //         return $q->leave_type_name == $array[5];
            //     })->values()->first();

            //     if (!$employeeValidation) {
            //         return redirect()->back()->with('error', "It seems like you're facing an issue where employee details cannot be matched in a specific row " . $row);
            //     }

            //     if (!$leaveValidation) {
            //         return redirect()->back()->with('error', "It seems like you're encountering an issue related to leave types not matching in a specific row " . $row);
            //     }
            // }

            Excel::import(new LeaveMasterImport($request->all()), $file);

            return back()->with('success', 'Employee information imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $import = new LeaveMasterImport($request->all());
            $import->import($file);
            foreach ($import->failures() as $failure) {
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
            }
        }
        return back()->with('success', 'Employee information imported successfully.');
    }

    public function export()
    {

        $employees = Employee::where('status', UserStatus::$ACTIVE)->with('department', 'branch', 'workshift', 'leaveMaster')
            ->orderBy('branch_id')->orderBy('first_name')->get();
        $leaveType = LeaveType::all();
        $leaveMaster = LeaveMaster::all();
        $extraData = [];

        foreach ($employees as $key => $Data) {
            foreach ($leaveType as $key => $Type) {
                $leaveData = $leaveMaster->filter(function ($q) use ($Data, $Type) {
                    return $q->finger_print_id == $Data->finger_id && $Type->leave_type_id == $q->leave_type_id;
                })->values()->first();
                $dataset[] = [
                    $key + 1,
                    $Data->branch->branch_name,
                    $Data->department->department_name,
                    $Data->first_name . ' ' . $Data->last_name,
                    $Data->finger_id,
                    $Type->leave_type_name,
                    isset($leaveData) && $leaveData->num_of_day > 0 ? $leaveData->num_of_day : '0',
                ];
            }
        }

        $heading = [
            [
                'SL.NO',
                'BRANCH NAME',
                'DEPARTMENT NAME',
                'EMPLOYEE NAME',
                'EMPLOYEE ID',
                'LEAVE TYPE',
                'YEARLY LIMIT',
            ],
        ];

        $extraData['heading'] = $heading;
        $filename = 'LeaveMasterInformation-' . DATE('dmYHis') . '.xlsx';
        return Excel::download(new LeaveMasterExport($dataset, $extraData), $filename);
    }
}

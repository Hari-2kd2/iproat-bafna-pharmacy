<?php

namespace App\Http\Controllers\Api;

use DateTime;
use Carbon\Carbon;
use App\Model\Employee;
use App\Model\LeaveType;
use App\Components\Common;
use App\Model\Designation;
use Illuminate\Http\Request;
use App\Model\LeaveConfigure;
use App\Model\LeavePermission;
use App\Model\PermissionMaster;
use App\Model\PaidLeaveApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Repositories\LeaveRepository;
use Illuminate\Support\Facades\Route;
use App\Repositories\CommonRepository;
use App\Http\Requests\ApplyForPermissionRequest;

class ApplyForPermissionController extends Controller
{
    protected $commonRepository;
    protected $leaveRepository;

    public function __construct(CommonRepository $commonRepository, LeaveRepository $leaveRepository)
    {
        $this->commonRepository = $commonRepository;
        $this->leaveRepository  = $leaveRepository;
    }

    public function index(Request $request)
    {
        $data = [];
        $employee = Employee::where('user_id', $request->employee_id)->first();

        $permission_data = LeavePermission::with(['employee'])
            ->where('employee_id', $employee->employee_id)
            // ->where('leave_permission_date','>=',date('Y-m-d'))
            ->orderBy('leave_permission_date', 'desc')
            ->get();
        $permission_status = "";

        foreach ($permission_data as $permission_row) {

            if ($permission_row->status == 2) {
                $permission_status = "Rejected";
            } elseif (($permission_row->status == 1) && ($permission_row->department_approval_status == 1)) {
                $permission_status = "Approved";
            } elseif (($permission_row->status == 1) && ($permission_row->department_approval_status == 0)) {
                $permission_status = "Pending";
            } else {
                $permission_status = "Pending";
            }
            $data[] = array(
                'leave_permission_date' => date("d-m-Y", strtotime($permission_row->leave_permission_date)),
                'permission_duration' => $permission_row->permission_duration,
                'from_time' => $permission_row->from_time,
                'to_time' => $permission_row->to_time,
                'leave_permission_purpose' => $permission_row->leave_permission_purpose,
                'permission_status' => $permission_status,
                'p_status' => $permission_row->status,
                'first_name' => $employee->first_name,
                'finger_id' => $employee->finger_id,
                'remark' => $permission_row->head_remarks,
            );
        }

        if ($data) {
            return response()->json([
                'status' => true,
                'data'         => $data,
                'message'      => 'Permission Request Details Successfully Received',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No Data Found',
            ], 200);
        }
    }

    public function create()
    {
        return response()->json([
            'message' => 'No Data Found',
            'status' => false,
        ], 200);
    }


    public function store(Request $request)
    {

        $employee_data = Employee::where('employee_id', $request->employee_id)->first();

        $input                            = $request->all();
        $input['leave_permission_date']   = dateConvertFormtoDB($request->permission_date);
        $input['permission_duration']     = $request->permission_duration;
        $input['leave_permission_purpose'] = $request->purpose;
        $input['department_head']         = $employee_data->supervisor_id;
        $input['from_time']               = $request->from_time;
        $input['to_time']                 = $request->to_time;
        $input['branch_id'] = $employee_data->branch_id;

        if ($employee_data->supervisor_id == '') {
            return response()->json([
                'message' => 'Department Head Data Not Given',
                'status' => false,
            ], 200);
        } elseif (($request->permission_date == '' || $request->permission_date == '0000-00-00')) {
            return response()->json([
                'message' => 'Permission Date Not Given',
                'status' => false,
            ], 200);
        } elseif ($request->permission_duration == '') {
            return response()->json([
                'message' => 'Permission Duration Not Given',
                'status' => false,
            ], 200);
        } elseif ($request->purpose == '') {
            return response()->json([
                'message' => 'Permission Purpose Not Given',
                'status' => false,
            ], 200);
        } elseif ($request->from_time == '') {
            return response()->json([
                'message' => 'Permission From Time Not Given',
                'status' => false,
            ], 200);
        } elseif ($request->to_time == '') {
            return response()->json([
                'message' => 'Permission To Time Not Given',
                'status' => false,
            ], 200);

            // } elseif(date('Y-m-d',strtotime($request->permission_date)) < date('Y-m-d')) {
            //     return response()->json([
            //         'message' => 'Permission cannot be applied for completed days.', 
            //         'status' => false,
            //     ], 200);

        } else {
            $hod = Employee::where('employee_id', $employee_data->supervisor_id)->first();
            $if_exists = LeavePermission::where('employee_id', $request->employee_id)->where('leave_permission_date', dateConvertFormtoDB($request->permission_date))->first();

            // $checkpermissions = LeavePermission::whereMonth('leave_permission_date', '=', date('m', $request->permission_date))->whereYear('leave_permission_date', '=', date('Y', $request->permission_date))
            //     ->where('department_approval_status', '1')->where('employee_id', $request->employee_id)->where('status', 1)->count();

            // if ($checkpermissions) {
            //     return response()->json([
            //         'message' => 'Permission Request limit exceeded.',
            //         'status' => false,
            //     ], 200);
            // }

            if (!$if_exists) {

                LeavePermission::create($input);
                if ($hod != '') {
                    if ($hod->email) {
                        $maildata = Common::mail('emails/mail', $hod->email, 'Permission Request Notification', ['head_name' => $hod->first_name . ' ' . $hod->last_name, 'request_info' => $employee_data->first_name . ' ' . $employee_data->last_name . ', have requested for permission (Purpose: ' . $request->purpose . ') On ' . ' ' . dateConvertFormtoDB($request->permission_date), 'status_info' => '']);
                    }
                }

                return response()->json([
                    'message' => 'Permission Request Sent Successfully.',
                    'status' => true,
                    // 'data'=> $permission_data,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Permission Request Already Exist.',
                    'status' => false,
                ], 200);
            }
        }
    }

    public function applyForTotalNumberOfPermissions(Request $request)
    {
        info($request->all());

        $permission_date = dateConvertFormtoDB($request->permission_date);
        $employee_id = $request->employee_id;
        $Year = date("Y", strtotime($permission_date));
        $Month = (int) date("m", strtotime($permission_date));

        $permissionMaster = PermissionMaster::first();
        $leavePermission =  LeavePermission::whereMonth('leave_permission_date', '=', $Month)->whereYear('leave_permission_date', '=', $Year)
            ->where('department_approval_status', '1')->where('employee_id', $employee_id)->where('status', 1);

        $totalCount =  $leavePermission->count();
        $balanceCount = $permissionMaster->monthly_limit - $totalCount;
        $totalHours = $leavePermission->pluck('permission_duration');
        $minutes = 0;
        foreach ($totalHours as $key => $value) {
            $minutes += minutes(date('H:i:s', strtotime($value)));
        }

        $time = hoursandmins($minutes);
        $takenHour = $time ?? '00:00:00';
        $timeTotal = $permissionMaster->total_duration;
        $origin = new DateTime($takenHour);
        $target = new DateTime($timeTotal);
        $interval = $origin->diff($target);

        return response()->json([
            'status' => true,
            'data' => [
                'duration' => $time ?? '00:00:00',
                'hours' => $time ? date('H', strtotime($time)) : '00',
                'minutes' => $time ? date('i', strtotime($time)) : '00',
                'permission_master' => $permissionMaster,
                'balance_count' => $balanceCount,
                'balance_hour' => $interval->format('%H') . ':' . $interval->format('%I') . ':' . $interval->format('%S')
            ]
        ]);
    }
}

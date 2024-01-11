<?php

namespace App\Repositories;

use App\Model\OnDuty;
use App\Model\CompOff;
use App\Model\Employee;
use App\Model\Incentive;
use App\Model\WeeklyHoliday;
use App\Model\LeavePermission;
use App\Model\LeaveApplication;
use Illuminate\Support\Facades\DB;
use App\Lib\Enumerations\UserStatus;
use App\Lib\Enumerations\AppConstant;
use App\Lib\Enumerations\LeaveStatus;

class AttendanceRepository
{

    public function getEmployeeDailyAttendance($date = false, $department_id, $attendance_status, $branch_name = null)
    {
        if ($date) {
            $data = dateConvertFormtoDB($date);
        } else {
            $data = date("Y-m-d");
        }

        $queryResults = DB::select("call `SP_DepartmentDailyAttendance`('" . $data . "', '" . $department_id . "','" . $attendance_status . "')");
        $results = [];

        $compOffList = CompOff::all();
        $incentiveList = Incentive::all();
        $employeeList = Employee::where('status', 1)->get();

        foreach ($queryResults as $value) {

            if ($branch_name == null || $branch_name == $value->branch_name) {
                $tempArr = [];
                $compOff = [];
                $incentive = null;

                // $comptime = strtotime('00:00:00');
                $totalduration = '';

                $compOff = $compOffList->filter(function ($compOf) use ($value) {
                    if ($compOf->employee_attendance_id == $value->employee_attendance_id && $value->employee_id == $compOf->employee_id) {
                        return $compOf;
                    }
                })->values();


                foreach ($compOff as $coData) {
                    if ($coData->off_timing == 0) {
                        $compOData = '04:00:00';
                    } elseif ($coData->off_timing == 1) {
                        $compOData = '08:00:00';
                    } else {
                        $compOData = '00:00:00';
                    }
                    // $comptime = strtotime($compOData);

                    $wtime = date('H:i', strtotime($value->working_time));
                    $whour_minit = explode(':', $wtime);
                    $wtotalHour = $whour_minit[0];
                    $wtotalMinit = $whour_minit[1];
                    $cTime = date('H:i', strtotime($compOData));
                    $chour_minit = explode(':', $cTime);
                    $ctotalHour = $chour_minit[0];
                    $ctotalMinit = $chour_minit[1];
                    $hour_time = $wtotalHour - $ctotalHour;
                    $min_time = $wtotalMinit - $ctotalMinit;
                    $work_time = $hour_time . ':' . $min_time;
                    $totalduration = date('H:i', strtotime($work_time));
                }

                $compOffData =  $compOffList->filter(function ($compOf) use ($value) {
                    if ($compOf->off_date == $value->date && $value->employee_id == $compOf->employee_id) {
                        return $compOf;
                    }
                })->values();

                $incentive = $incentiveList->filter(function ($incentives) use ($value) {
                    if ($incentives->employee_attendance_id == $value->employee_attendance_id) {
                        return $incentives;
                    }
                })->values()->first();

                $employee = $employeeList->filter(function ($employees) use ($value) {
                    if ($employees->employee_id == $value->employee_id) {
                        return $employees;
                    }
                })->values()->first();

                // $incentive = Incentive::where('employee_attendance_id', $value->employee_attendance_id)->first();
                // $compOffData = CompOff::where('employee_id', $value->employee_id)->where('off_date', $value->date)->get();
                // $employee = Employee::where('employee_id', $value->employee_id)->where('status', 1)->first();

                $secs = strtotime('00:00:00');
                $cstatus = 0;
                $totalcompoff = '';
                $compoffArray = '';
                if ($compOffData != '') {
                    foreach ($compOffData as $cData) {
                        if ($cData->off_timing == 0) {
                            $compOData = '04:00:00';
                            $compOffDate = $cData->working_date;
                            $cstatus = 1;
                        } elseif ($cData->off_timing == 1) {
                            $compOData = '08:00:00';
                            $compOffDate = $cData->working_date;
                            $cstatus = 1;
                        } else {
                            $compOData = '00:00:00';
                            $compOffDate = '';
                            $cstatus = 0;
                        }

                        $secs = strtotime($compOData);
                        $totalcompoff = date("H:i:s", ($secs));
                        $compoffArray .= $compOData . ':(' . $compOffDate . ' ) ,';
                    }
                }
                // if(isset($value->working_time) && $value->working_time != '00:00:00'){
                //         $compoffArray = $compoffDats.''.date('H:i', strtotime($value->working_time));
                // }

                $incentive_status = 0;
                $balance_hour = '';

                // if($value->working_time >= '12:00:00'){
                $compOffDatacheck = CompOff::where('employee_attendance_id', $value->employee_attendance_id)->first();
                if ($compOffDatacheck != '') {
                    if ($employee->incentive == 1 && $employee->salary_limit == 1 && $compOffDatacheck->balance_hour >= '04:00:00') {
                        $incentive_status = 1;
                    } else {
                        $incentive_status = 0;
                    }
                    $balance_hour = $compOffDatacheck->balance_hour;
                    $present = "Present";
                }

                $permissionondate = LeavePermission::where('leave_permission_date', $value->date)->where('employee_id', $value->employee_id)->where('department_approval_status', '1')->where('status', 1)->first();
                if ($permissionondate != '') {
                    $duration = $permissionondate->permission_duration;
                    $permission_status = 1;
                } else {
                    $duration = '';
                    $permission_status = 0;
                }

                $start_date = date('Y-m-01', strtotime($value->date));
                $end_date = date('Y-m-d', strtotime($value->date));
                $govtHolidays = DB::select(DB::raw('call SP_getHoliday("' . $start_date . '","' . $end_date . '")'));
                $weeklyHolidays = DB::select(DB::raw('call SP_getWeeklyHoliday("' . $employee->employee_id . '","' . date('Y-m', strtotime($start_date)) . '")'));
                $weeklyHolidaysDates = WeeklyHoliday::where('employee_id', $value->employee_id)->where('month', date('Y-m', strtotime($start_date)))->first();
                $data = findFromDateToDateToAllDate($start_date, $end_date);
                $dateArr['holidays'] = $dateArr['week_days'] = [];
                // $employee = Employee::where('employee_id', $value->employee_id)->first();
                $incentive_eligiblity = '0';
                foreach ($data as $key => $valuedata) {

                    $ifHoliday = $this->ifHoliday($govtHolidays, $value->date, $value->employee_id, $weeklyHolidays, $weeklyHolidaysDates);

                    if ($ifHoliday) {
                        $incentive_eligiblity = '0';
                    }

                    $incentive_eligiblity = '1';
                }

                $tempArr = $value;
                $tempArr->comp_off = $compOff;
                $tempArr->incentive = $incentive;
                $tempArr->incentive_status = $incentive_status;
                $tempArr->permission_status = $permission_status;
                $tempArr->duration = $duration;
                $tempArr->comptotalduration = $totalduration;
                $tempArr->comp_off_hours = $totalcompoff;
                $tempArr->balance_hour = $balance_hour;
                $tempArr->comp_off_data = $compoffArray;
                $tempArr->cstatus = $cstatus;
                $tempArr->incentive_eligiblity = $incentive_eligiblity;

                $results[] = $tempArr;
            }
        }

        return $results;
    }

    public function findAttendanceMusterReport($start_date, $end_date, $employee_id = '', $department_id = '', $branch_id = '')
    {
        $data = findMonthFromToDate($start_date, $end_date);

        $qry = '1 ';

        if ($employee_id != '') {
            $qry .= ' AND employee.employee_id=' . $employee_id;
        }
        if ($department_id != '') {
            $qry .= ' AND employee.department_id=' . $department_id;
        }
        if ($branch_id != '') {
            $qry .= ' AND employee.branch_id=' . $branch_id;
        }

        $employees = Employee::select(DB::raw('CONCAT(COALESCE(employee.first_name,\'\'),\' \',COALESCE(employee.last_name,\'\')) AS fullName'), 'designation_name', 'department_name', 'branch_name', 'finger_id', 'employee_id')
            ->join('designation', 'designation.designation_id', 'employee.designation_id')
            ->join('department', 'department.department_id', 'employee.department_id')
            ->join('branch', 'branch.branch_id', 'employee.branch_id')->orderBy('branch.branch_name', 'ASC')->whereRaw($qry)
            ->where('salary_limit', AppConstant::$SALARY_LESS_THAN_20K)->where('status', UserStatus::$ACTIVE)->get();

        $attendance = DB::table('view_employee_in_out_data')->whereBetween('date', [$start_date, $end_date])->get();

        $govtHolidays = DB::select(DB::raw('call SP_getHoliday("' . $start_date . '","' . $end_date . '")'));

        $dataFormat = [];
        $tempArray = [];

        foreach ($employees as $employee) {

            foreach ($data as $key => $value) {

                $tempArray['employee_id'] = $employee->employee_id;
                $tempArray['finger_id'] = $employee->finger_id;
                $tempArray['fullName'] = $employee->fullName;
                $tempArray['designation_name'] = $employee->designation_name;
                $tempArray['department_name'] = $employee->department_name;
                $tempArray['branch_name'] = $employee->branch_name;
                $tempArray['date'] = $value['date'];
                $tempArray['day'] = $value['day'];
                $tempArray['day_name'] = $value['day_name'];

                $hasAttendance = $this->hasEmployeeMusterAttendance($attendance, $employee->finger_id, $value['date']);

                $ifPublicHoliday = $this->ifPublicHoliday($govtHolidays, $value['date']);

                if ($ifPublicHoliday) {
                    $tempArray['attendance_status'] = 'holiday';
                    $tempArray['shift_name'] = $hasAttendance['shift_name'];
                    $tempArray['in_time'] = $hasAttendance['in_time'];
                    $tempArray['out_time'] = $hasAttendance['out_time'];
                    $tempArray['working_time'] = $hasAttendance['working_time'];
                    $tempArray['over_time'] = $hasAttendance['over_time'];
                    $tempArray['over_time_status'] = $hasAttendance['over_time_status'];
                    $tempArray['employee_attendance_id'] = $hasAttendance['employee_attendance_id'];
                } elseif ($hasAttendance) {
                    $tempArray['attendance_status'] = 'present';
                    $tempArray['shift_name'] = $hasAttendance['shift_name'];
                    $tempArray['in_time'] = $hasAttendance['in_time'];
                    $tempArray['out_time'] = $hasAttendance['out_time'];
                    $tempArray['working_time'] = $hasAttendance['working_time'];
                    $tempArray['over_time'] = $hasAttendance['over_time'];
                    $tempArray['over_time_status'] = $hasAttendance['over_time_status'];
                    $tempArray['employee_attendance_id'] = $hasAttendance['employee_attendance_id'];
                } else {

                    $tempArray['attendance_status'] = 'absence';
                    $tempArray['shift_name'] = '';
                    $tempArray['in_time'] = '';
                    $tempArray['out_time'] = '';
                    $tempArray['over_time'] = '';
                    $tempArray['working_time'] = '';
                    $tempArray['over_time_status'] = '';
                    $tempArray['employee_attendance_id'] = '';
                }

                $dataFormat[$employee->finger_id][] = $tempArray;
            }
        }

        return $dataFormat;
    }
    public function hasEmployeeMusterAttendance($attendance, $finger_print_id, $date)
    {
        $dataFormat = [];
        $dataFormat['in_time'] = '';
        $dataFormat['out_time'] = '';
        $dataFormat['over_time'] = '';
        $dataFormat['working_time'] = '';
        $dataFormat['over_time_status'] = '';
        $dataFormat['shift_name'] = '';
        $dataFormat['employee_attendance_id'] = '';

        foreach ($attendance as $key => $val) {
            $compOffData = CompOff::where('employee_attendance_id', $val->employee_attendance_id)->first();
            if ($compOffData != '') {
                if ($compOffData->off_timing == 0) {
                    $compOData = '04:00:00';
                } elseif ($compOffData->off_timing == 1) {
                    $compOData = '08:00:00';
                } else {
                    $compOData = '00:00:00';
                }
                $wtime = date('H:i', strtotime($val->working_time));
                $whour_minit = explode(':', $wtime);
                $wtotalHour = $whour_minit[0];
                $wtotalMinit = $whour_minit[1];
                $cTime = date('H:i', strtotime($compOData));
                $chour_minit = explode(':', $cTime);
                $ctotalHour = $chour_minit[0];
                $ctotalMinit = $chour_minit[1];
                $hour_time = $wtotalHour - $ctotalHour;
                $min_time = $wtotalMinit - $ctotalMinit;
                $work_time = $hour_time . ':' . $min_time;
                $totalduration = date('H:i', strtotime($work_time));
                $work_time = $totalduration;
                $over_time = $compOffData->balance_hour;
            } else {
                $work_time = $val->working_time;
                $over_time = $val->over_time;
            }
            // dd($val);
            if (($val->finger_print_id == $finger_print_id && $val->date == $date && $val->in_time != null)) {
                $dataFormat['shift_name'] = $val->shift_name;
                $dataFormat['in_time'] = $val->in_time;
                $dataFormat['out_time'] = $val->out_time;
                $dataFormat['over_time'] = $over_time;
                $dataFormat['working_time'] = $work_time;
                $dataFormat['over_time_status'] = $val->over_time_status;
                $dataFormat['employee_attendance_id'] = $val->employee_attendance_id;
                return $dataFormat;
            }
        }
        return $dataFormat;
    }
    public function ifPublicHoliday($govtHolidays, $date)
    {
        $govt_holidays = [];

        foreach ($govtHolidays as $holidays) {
            $start_date = $holidays->from_date;
            $end_date = $holidays->to_date;
            while (strtotime($start_date) <= strtotime($end_date)) {
                $govt_holidays[] = $start_date;
                $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
            }
        }

        foreach ($govt_holidays as $val) {
            if ($val == $date) {
                return true;
            }
        }
        return false;
    }

    public function getEmployeeMonthlyAttendance($from_date, $to_date, $employee_id)
    {
        $monthlyAttendanceData = DB::select("CALL `SP_monthlyAttendance`('" . $employee_id . "','" . $from_date . "','" . $to_date . "')");
        $workingDates = $this->number_of_working_days_date($from_date, $to_date, $employee_id);
        $employeeLeaveRecords = $this->getEmployeeLeaveRecord($from_date, $to_date, $employee_id);
        $employeeHolidayRecords = $this->getEmployeeHolidayRecord($from_date, $to_date, $employee_id);
        // $employeePermissionRecords = $this->getEmployeePermissionRecord($from_date, $to_date, $employee_id);

        $dataFormat = [];
        $tempArray = [];
        $present = null;

        // dd($workingDates);

        if ($workingDates && $monthlyAttendanceData) {
            foreach ($workingDates as $key => $data) {
                $flag = 0;
                // dd($monthlyAttendanceData);
                foreach ($monthlyAttendanceData as $value) {
                    if ($data == $value->date && $value->working_time != '00:00:00') {
                        $flag = 1;
                        break;
                    }
                }

                $permissionondate = LeavePermission::where('leave_permission_date', $value->date)->where('employee_id', $value->employee_id)->where('department_approval_status', '1')->where('status', 1)->first();

                if ($permissionondate != '') {
                    $duration = $permissionondate->permission_duration;
                    $permission_status = 1;
                } else {
                    $duration = '';
                    $permission_status = 0;
                }

                $comptime = strtotime('00:00:00');
                $totalduration = '';

                $compOff = CompOff::where('employee_attendance_id', $value->employee_attendance_id)->get();

                foreach ($compOff as $coData) {

                    if ($coData->off_timing == 0) {
                        $compOData = '04:00:00';
                    } elseif ($coData->off_timing == 1) {
                        $compOData = '08:00:00';
                    } else {
                        $compOData = '00:00:00';
                    }

                    $wtime = date('H:i', strtotime($value->working_time));
                    $whour_minit = explode(':', $wtime);
                    $wtotalHour = $whour_minit[0];
                    $wtotalMinit = $whour_minit[1];
                    $cTime = date('H:i', strtotime($compOData));
                    $chour_minit = explode(':', $cTime);
                    $ctotalHour = $chour_minit[0];
                    $ctotalMinit = $chour_minit[1];
                    $hour_time = $wtotalHour - $ctotalHour;
                    $min_time = $wtotalMinit - $ctotalMinit;
                    $work_time = $hour_time . ':' . $min_time;
                    $totalduration = date('H:i', strtotime($work_time));
                }

                $compendatedData = '';
                $compoff_status = 0;
                $compOffapplied = CompOff::where('off_date', $value->date)->where('employee_id', $value->employee_id)->get();

                $comptime = '00:00:00';
                $totalHour = 0;
                $totalMinit = 0;
                $compoffDats = '';

                if ($compOffapplied != '') {
                    foreach ($compOffapplied as $cData) {
                        if ($cData->off_timing == '0') {
                            $compendatedData = '04:00:00';
                            $compOffDate = $cData->working_date;
                            $compoff_status = 1;
                        } elseif ($cData->off_timing == '1') {
                            $compendatedData = '08:00:00';
                            $compOffDate = $cData->working_date;
                            $compoff_status = 2;
                        } else {
                            $compendatedData = '00:00:00';
                            $compoff_status = 0;
                            $compOffDate = '';
                        }

                        $d = date('H:i', strtotime($compendatedData));
                        $hour_minit = explode(':', $d);
                        $totalHour += $hour_minit[0];
                        $totalMinit += $hour_minit[1];
                        $comptime = $totalHour . ':' . $totalMinit;

                        $secs = strtotime($compendatedData);
                        $totalcompoff = date("H:i:s", ($secs));
                        $compoffDats .= 'Comp : ' . $compendatedData . ':(' . $compOffDate . ' ) ,';
                    }
                }

                // dd($totalHour.':'.totalMinit);
                if (isset($totalduration) && $totalduration != '' && $totalduration != '00:00') {
                    $compoffArray = $compoffDats . ' Worked Time : ' . date('H:i', strtotime($totalduration));
                    $worktime = '00:00:00';
                    $compworktime = $totalduration;
                } elseif (isset($value->working_time) && $value->working_time != '00:00:00') {
                    $compoffArray = $compoffDats . ' Worked Time : ' . date('H:i', strtotime($value->working_time));
                    $worktime = $value->working_time;
                    $compworktime = '00:00:00';
                } else {
                    $compoffArray = $compoffDats;
                    $worktime = '00:00:00';
                    $compworktime = '00:00:00';
                }

                $wtime = date('H:i', strtotime($worktime));
                $whour_minit = explode(':', $wtime);
                $wtotalHour = $whour_minit[0];
                $wtotalMinit = $whour_minit[1];
                $cTime = date('H:i', strtotime($comptime));
                $chour_minit = explode(':', $cTime);
                $ctotalHour = $chour_minit[0];
                $ctotalMinit = $chour_minit[1];
                $afterCompTime = date('H:i', strtotime($compworktime));
                $achour_minit = explode(':', $afterCompTime);
                $actotalHour = $achour_minit[0];
                $actotalMinit = $achour_minit[1];

                $hour_time = $wtotalHour + $ctotalHour + $actotalHour;
                $min_time = $wtotalMinit + $ctotalMinit + $actotalMinit;
                $work_time = $hour_time . ':' . $min_time;
                $totalduration = date('H:i', strtotime($hour_time . ':' . $min_time));

                $tempArray['total_present'] = null;

                if ($flag == 0) {
                    $totalduration = '00:00';
                    $compoffArray = '';

                    $tempArray['employee_id'] = $value->employee_id;
                    $tempArray['fullName'] = $value->fullName;
                    $tempArray['department_name'] = $value->department_name;
                    $tempArray['finger_print_id'] = $value->finger_print_id;
                    $tempArray['date'] = $data;
                    $tempArray['working_time'] = '';
                    $tempArray['in_time'] = '';
                    $tempArray['out_time'] = '';
                    $tempArray['lateCountTime'] = '';
                    $tempArray['ifLate'] = '';
                    $tempArray['totalLateTime'] = '';
                    $tempArray['workingHour'] = '';
                    $tempArray['permission_status'] = $permission_status;
                    $tempArray['permission_duration'] = $duration;
                    $tempArray['total_present'] = $present;
                    $tempArray['comptotalduration'] = $totalduration;
                    $tempArray['compensatedDuration'] = $compendatedData;
                    $tempArray['comp_off_data'] = $compoffArray;
                    $tempArray['totalduration'] = $totalduration;

                    if (in_array($data, $employeeLeaveRecords)) {
                        $tempArray['action'] = 'Leave';
                    } elseif (in_array($data, $employeeHolidayRecords)) {
                        $tempArray['action'] = 'Holiday';
                    } else {
                        $tempArray['action'] = 'Absence';
                    }

                    $dataFormat[] = $tempArray;
                } else {
                    if ($value->working_time != null && !empty($compOffapplied)) {
                        $att_status = 'Present';
                    } elseif (in_array($data, $employeeHolidayRecords)) {
                        $att_status = 'Holiday';
                    } else {
                        $att_status = 'Absence';
                    }

                    $tempArray['total_present'] = $present += 1;
                    $tempArray['employee_id'] = $value->employee_id;
                    $tempArray['fullName'] = $value->fullName;
                    $tempArray['department_name'] = $value->department_name;
                    $tempArray['finger_print_id'] = $value->finger_print_id;
                    $tempArray['date'] = $value->date;
                    $tempArray['working_time'] = $value->working_time;
                    $tempArray['in_time'] = $value->in_time;
                    $tempArray['out_time'] = $value->out_time;
                    $tempArray['lateCountTime'] = $value->lateCountTime;
                    $tempArray['ifLate'] = $value->ifLate;
                    $tempArray['totalLateTime'] = $value->totalLateTime;
                    $tempArray['workingHour'] = $value->workingHour;
                    $tempArray['action'] = $att_status;
                    $tempArray['permission_status'] = $permission_status;
                    $tempArray['permission_duration'] = $duration;
                    $tempArray['comptotalduration'] = $totalduration;
                    $tempArray['compensatedDuration'] = $compendatedData;
                    $tempArray['compoff_status'] = $compoff_status;
                    $tempArray['comp_off_data'] = $compoffArray;
                    $tempArray['totalduration'] = $totalduration;

                    $dataFormat[] = $tempArray;
                }
            }
        }

        foreach ($dataFormat as $key => $value) {

            if (($value['action'] == 'Holiday' || $value['action'] == 'Leave') && $key != 0 && $key < (count($dataFormat) - 1)) {

                $previous = $key - 1;
                $previousDay = $dataFormat[$previous]['action'];
                $next = $key + 1;
                $nextDay = $dataFormat[$next]['action'];

                if ($nextDay == 'Holiday' || $value['action'] == 'Leave') {
                    do {
                        $next++;
                        $nextDay = $dataFormat[$next]['action'];
                    } while ($nextDay == 'Absence' && $next < (count($dataFormat) - 1));
                }

                if ($previousDay == 'Absence' && $nextDay == 'Absence') {
                    $dataFormat[$key]['action'] = 'Absence';
                }
            }
        }

        return $dataFormat;
    }

    public function number_of_working_days_date($from_date, $to_date, $employee_id)
    {
        $holidays = DB::select(DB::raw('call SP_getHoliday("' . $from_date . '","' . $to_date . '")'));
        $public_holidays = [];
        foreach ($holidays as $holidays) {
            $start_date = $holidays->from_date;
            $end_date = $holidays->to_date;
            while (strtotime($start_date) <= strtotime($end_date)) {
                $public_holidays[] = $start_date;
                $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
            }
        }

        // $weeklyHolidays     = DB::select(DB::raw('call SP_getWeeklyHoliday()'));
        // $weeklyHolidayArray = [];
        // foreach ($weeklyHolidays as $weeklyHoliday) {
        //     $weeklyHolidayArray[] = $weeklyHoliday->day_name;
        // }

        $weeklyHolidayArray = WeeklyHoliday::select('day_name')
            ->where('employee_id', $employee_id)
            ->where('month', date('Y-m', strtotime($from_date)))
            ->orWhere('month', date('Y-m', strtotime($to_date)))
            ->first();

        $target = strtotime($from_date);
        $workingDate = [];

        while ($target <= strtotime(date("Y-m-d", strtotime($to_date)))) {

            //get weekly  holiday name
            $timestamp = strtotime(date('Y-m-d', $target));
            $dayName = date("l", $timestamp);

            // if (!in_array(date('Y-m-d', $target), $public_holidays) && !in_array($dayName, $weeklyHolidayArray->toArray())) {
            //     array_push($workingDate, date('Y-m-d', $target));
            // }

            // if (!in_array(date('Y-m-d', $target), $public_holidays)) {
            //     array_push($workingDate, date('Y-m-d', $target));
            // }

            \array_push($workingDate, date('Y-m-d', $target));

            if (date('Y-m-d') <= date('Y-m-d', $target)) {
                break;
            }
            $target += (60 * 60 * 24);
        }
        return $workingDate;
    }

    public function getEmployeeLeaveRecord($from_date, $to_date, $employee_id)
    {
        $queryResult = LeaveApplication::select('application_from_date', 'application_to_date')
            ->where('status', LeaveStatus::$APPROVE)
            ->where('application_from_date', '>=', $from_date)
            ->where('application_to_date', '<=', $to_date)
            ->where('employee_id', $employee_id)
            ->get();
        $leaveRecord = [];
        foreach ($queryResult as $value) {
            $start_date = $value->application_from_date;
            $end_date = $value->application_to_date;
            while (strtotime($start_date) <= strtotime($end_date)) {
                $leaveRecord[] = $start_date;
                $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
            }
        }
        return $leaveRecord;
    }

    public function getEmployeeHolidayRecord($from_date, $to_date, $employee_id)
    {
        $queryResult = WeeklyHoliday::select('weekoff_days')
            ->where('employee_id', $employee_id)
            ->whereBetween('month', [date('Y-m', strtotime($from_date)), date('Y-m', strtotime($to_date))])
            ->first();

        $holidayRecord = [];
        if ($queryResult) {
            foreach (\json_decode($queryResult['weekoff_days']) as $value) {
                $holidayRecord[] = $value;
            }
        }
        return $holidayRecord;
    }

    public function findAttendanceSummaryReport($month, $start_date, $end_date, $branch_name = null, $department_name = null)
    {
        $data = findFromDateToDateToAllDate($start_date, $end_date);

        $attendance = DB::table('view_employee_in_out_data')->select('finger_print_id', 'date', 'in_time', 'shift_name', 'inout_status', 'out_time', 'working_time')->whereBetween('date', [$start_date, $end_date])->get();

        // $regularEmployeeIds = DB::table('view_employee_in_out_data')->select('finger_print_id')->whereBetween('date', [$start_date, $end_date])->groupBy('finger_print_id')->pluck('finger_print_id')->toArray();

        if (session('logged_session_data.role_id') == 1 || session('logged_session_data.role_id') == 2) {
            $employees = Employee::select('employee.first_name', 'employee.last_name', 'employee.updated_at', 'gender', 'status', 'department_name', 'branch_name', 'designation_name', 'finger_id', 'employee_id')
                ->join('designation', 'designation.designation_id', 'employee.designation_id')
                ->join('department', 'department.department_id', 'employee.department_id')
                ->join('branch', 'branch.branch_id', 'employee.branch_id')
                ->orderBy('branch.branch_name', 'ASC');
            // ->where('status', UserStatus::$ACTIVE)->get();
            // ->whereIn('employee.finger_id', $regularEmployeeIds);

            if ($branch_name != null) {
                $employees = $employees->where('branch.branch_name', $branch_name);
            }

            if ($department_name != null) {
                $employees = $employees->where('department.department_name', $department_name);
            }

            $employees = $employees->get();
        } else {
            $employees = Employee::select('employee.first_name', 'employee.last_name', 'employee.updated_at', 'gender', 'status', 'department_name', 'branch_name', 'designation_name', 'finger_id', 'employee_id')
                ->join('designation', 'designation.designation_id', 'employee.designation_id')
                ->join('department', 'department.department_id', 'employee.department_id')
                ->join('branch', 'branch.branch_id', 'employee.branch_id')
                ->orderBy('branch.branch_name', 'ASC')
                ->where('branch.branch_id', session('logged_session_data.branch_id'))
                ->where('department.department_id', session('logged_session_data.department_id'))
                // ->whereIn('employee.finger_id', $regularEmployeeIds)
                ->get();
        }

        $leave = LeaveApplication::select('application_from_date', 'application_to_date', 'employee_id', 'leave_type_name')
            ->join('leave_type', 'leave_type.leave_type_id', 'leave_application.leave_type_id')
            ->whereRaw("application_from_date >= '" . $start_date . "' and application_to_date <=  '" . $end_date . "'")
            ->where('status', LeaveStatus::$APPROVE)->get();

        $onDuty = OnDuty::select('application_from_date', 'application_to_date', 'employee_id')
            ->whereRaw("application_from_date >= '" . $start_date . "' and application_to_date <=  '" . $end_date . "'")
            ->where('status', LeaveStatus::$APPROVE)->get();

        $govtHolidays = DB::select(DB::raw('call SP_getHoliday("' . $start_date . '","' . $end_date . '")'));
        $totalWeeklyHolidays = WeeklyHoliday::whereBetween('month', [date('Y-m', strtotime($start_date)), date('Y-m', strtotime($start_date))])->get();
        $compOffappliedList = CompOff::whereBetween('off_date', [$start_date, $end_date])->get();

        $dataFormat = [];
        $tempArray = [];

        foreach ($employees as $employee) {
            $activeUser = $employee->status;
            $leftUser = $employee->status;
            // $weeklyHolidaysDates = WeeklyHoliday::where('employee_id', $employee->employee_id)->where('month', date('Y-m', strtotime($start_date)))->first();

            $weeklyHolidaysDates = $totalWeeklyHolidays->filter(function ($weeklyHoliday) use ($employee, $start_date) {
                if ($weeklyHoliday->employee_id == $employee->employee_id && $weeklyHoliday->month == date('Y-m', strtotime($start_date))) {
                    return $weeklyHoliday;
                }
            })->values();

            foreach ($data as $key => $value) {
                $tempArray['employee_id'] = $employee->employee_id;
                $tempArray['finger_id'] = $employee->finger_id;
                $tempArray['fullName'] = trim($employee->first_name . ' ' . $employee->last_name);
                $tempArray['designation_name'] = $employee->designation_name;
                $tempArray['department_name'] = $employee->department_name;
                $tempArray['branch_name'] = $employee->branch_name;
                $tempArray['gender'] = $employee->gender;
                $tempArray['status'] = $employee->status;
                $tempArray['date'] = $value['date'];
                $tempArray['day'] = $value['day'];
                $tempArray['day_name'] = $value['day_name'];

                $leftDate = date('Y-m-d', strtotime($employee->updated_at));

                $weeklyHolidayCollection = $totalWeeklyHolidays->map(function ($weeklyHoliday) {
                    return collect($weeklyHoliday->toArray())
                        ->only(['day_name', 'employee_id', 'weekoff_days', 'month'])
                        ->all();
                });

                $weeklyHolidays = $weeklyHolidayCollection->filter(function ($weeklyHoliday) use ($employee, $start_date) {
                    if ($weeklyHoliday['employee_id'] == $employee->employee_id && $weeklyHoliday['month'] == date('Y-m', strtotime($start_date))) {
                        return $weeklyHoliday;
                    }
                })->values();

                $weeklyHolidays = json_decode($weeklyHolidays);
                // $weeklyHolidays = DB::select(DB::raw('call SP_getWeeklyHoliday("' . $employee->employee_id . '","' . date('Y-m', strtotime($start_date)) . '")'));
                // dump($weeklyHolidays);

                // newly addded filter
                $employeeAttendance = $attendance->filter(function ($attData) use ($employee, $value) {
                    if ($attData->finger_print_id == $employee->finger_id && $attData->date == date('Y-m-d', strtotime($value['date']))) {
                        return $attData;
                    }
                })->values();

                $compOffapplied = $compOffappliedList->filter(function ($attData) use ($employee, $value) {
                    if ($attData->finger_print_id == $employee->finger_id && $attData->off_date == date('Y-m-d', strtotime($value['date']))) {
                        return $attData;
                    }
                })->values()->first();

                $hasAttendance = $this->hasEmployeeAttendance($employeeAttendance, $employee->finger_id, $value['date']);
                $hasCompOff = $this->hasCompOff($employeeAttendance, $employee->finger_id, $value['date'], $compOffapplied);
                $hasOnDuty = $this->hasOnDuty($onDuty, $employee->employee_id, $value['date']);

                if ($hasAttendance['status'] == true) {
                    $ifHoliday = $this->ifHoliday($govtHolidays, $value['date'], $employee->employee_id, $weeklyHolidays);
                    if ($ifHoliday['weekly_holiday'] == true) {
                        $tempArray['attendance_status'] = 'present';
                        $tempArray['gov_day_worked'] = 'no';
                        $tempArray['leave_type'] = '';
                        $tempArray['shift_name'] = $hasAttendance['shift_name'];
                        $tempArray['inout_status'] = $hasAttendance['inout_status'];
                    } elseif ($ifHoliday['govt_holiday'] == true) {
                        $tempArray['attendance_status'] = 'present';
                        $tempArray['gov_day_worked'] = 'yes';
                        $tempArray['leave_type'] = '';
                        $tempArray['shift_name'] = $hasAttendance['shift_name'];
                        $tempArray['inout_status'] = $hasAttendance['inout_status'];
                    } else {
                        $tempArray['attendance_status'] = 'present';
                        $tempArray['leave_type'] = '';
                        $tempArray['gov_day_worked'] = 'no';
                        $tempArray['shift_name'] = $hasAttendance['shift_name'];
                        $tempArray['inout_status'] = $hasAttendance['inout_status'];
                    }
                } elseif ($hasCompOff['status'] == true) {
                    $tempArray['attendance_status'] = 'CompOff';
                    $tempArray['gov_day_worked'] = 'no';
                    $tempArray['leave_type'] = '';
                    $tempArray['shift_name'] = $hasCompOff['shift_name'];
                    $tempArray['inout_status'] = $hasCompOff['inout_status'];
                } elseif ($hasOnDuty) {
                    $tempArray['attendance_status'] = 'OnDuty';
                    $tempArray['gov_day_worked'] = 'no';
                    $tempArray['leave_type'] = '';
                    $tempArray['shift_name'] = '';
                    $tempArray['inout_status'] = '';
                } else {

                    // if ($activeUser === UserStatus::$ACTIVE) {

                    $hasLeave = $this->ifEmployeeWasLeave($leave, $employee->employee_id, $value['date']);
                    $ifApplyLeaveOnHoliday = $this->ifHoliday($govtHolidays, $value['date'], $employee->employee_id, $weeklyHolidays, $weeklyHolidaysDates);

                    if ($hasLeave) {
                        if ($ifApplyLeaveOnHoliday['weekly_holiday'] == true) {
                            $tempArray['attendance_status'] = 'holiday';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = '';
                            $tempArray['shift_name'] = '';
                            $tempArray['inout_status'] = '';
                        } elseif ($ifApplyLeaveOnHoliday['govt_holiday'] == true) {
                            $tempArray['attendance_status'] = 'publicHoliday';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = '';
                            $tempArray['shift_name'] = '';
                            $tempArray['inout_status'] = '';
                        } else {
                            $tempArray['inout_status'] = '';
                            $tempArray['attendance_status'] = 'leave';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = $hasLeave;
                            $tempArray['shift_name'] = '';
                        }
                    } else {
                        if ($value['date'] > date("Y-m-d")) {
                            $tempArray['attendance_status'] = '';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = '';
                            $tempArray['shift_name'] = '';
                            $tempArray['inout_status'] = '';
                        } elseif ($leftUser === UserStatus::$INACTIVE && $value['date'] >= $leftDate) {
                            $tempArray['attendance_status'] = 'left';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = '';
                            $tempArray['shift_name'] = '';
                            $tempArray['inout_status'] = '';
                        } elseif ($hasCompOff['status'] == true) {
                            $tempArray['attendance_status'] = 'CompOff';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = '';
                            $tempArray['shift_name'] = $hasCompOff['shift_name'];
                            $tempArray['inout_status'] = $hasCompOff['inout_status'];
                        } elseif ($hasOnDuty == true) {
                            $tempArray['attendance_status'] = 'OnDuty';
                            $tempArray['gov_day_worked'] = 'no';
                            $tempArray['leave_type'] = '';
                            $tempArray['shift_name'] = '';
                            $tempArray['inout_status'] = '';
                        } else {
                            $ifHoliday = $this->ifHoliday($govtHolidays, $value['date'], $employee->employee_id, $weeklyHolidays, $weeklyHolidaysDates);
                            if ($ifHoliday['weekly_holiday'] == true) {
                                $tempArray['attendance_status'] = 'holiday';
                                $tempArray['gov_day_worked'] = 'no';
                                $tempArray['leave_type'] = '';
                                $tempArray['shift_name'] = '';
                                $tempArray['inout_status'] = '';
                            } elseif ($ifHoliday['govt_holiday'] == true) {
                                $tempArray['attendance_status'] = 'publicHoliday';
                                $tempArray['gov_day_worked'] = 'no';
                                $tempArray['leave_type'] = '';
                                $tempArray['shift_name'] = '';
                                $tempArray['inout_status'] = '';
                            } else {
                                $tempArray['attendance_status'] = 'absence';
                                $tempArray['gov_day_worked'] = 'no';
                                $tempArray['leave_type'] = '';
                                $tempArray['shift_name'] = '';
                                $tempArray['inout_status'] = '';
                            }
                        }
                    }
                    // } elseif (!$activeUser === UserStatus::$INACTIVE && $value['date'] > $leftDate) {
                    //     $tempArray['attendance_status'] = 'left';
                    //     $tempArray['gov_day_worked'] = 'no';
                    //     $tempArray['leave_type'] = '';
                    //     $tempArray['shift_name'] = '';
                    //     $tempArray['inout_status'] = '';
                    // }

                }

                $dataFormat[$employee->finger_id][] = $tempArray;
            }
        }
        // dd($dataFormat);

        foreach ($dataFormat as $key => $monthlyAtt) {
            foreach ($monthlyAtt as $key1 => $value) {
                if (($value['attendance_status'] == 'holiday' || $value['attendance_status'] == 'leave' ||  $value['attendance_status'] == 'publicHoliday') && $key1 != 0 && $key1 < count($monthlyAtt)) {
                    $previous = $key1 - 1;
                    $previousDay = $dataFormat[$key][$previous]['attendance_status'];
                    $next = $key1 + 1;
                    info($dataFormat[$key][$next]['attendance_status']);
                    $nextDay = $dataFormat[$key][$next]['attendance_status'];

                    if ($nextDay == 'holiday' || $nextDay == 'leave' ||  $nextDay == 'publicHoliday') {
                        do {
                            $next++;
                            $nextDay = $dataFormat[$key][$next]['attendance_status'];
                        } while ($nextDay == 'Absence' && $next < (count($dataFormat) - 1));
                    }

                    if ($previousDay == 'absence' && $nextDay == 'absence') {
                        $dataFormat[$key][$key1]['attendance_status'] = 'absence';
                    }
                    // }
                }
            }
        }
        // dd($dataFormat);

        return $dataFormat;
    }

    public function hasEmployeeAttendance($attendance, $finger_print_id, $date)
    {
        $temp = [];
        $temp['status'] = false;
        $temp['shift_name'] = '';
        $temp['inout_status'] = '';
        // dump($attendance, $finger_print_id, $date);
        foreach ($attendance as $key => $val) {
            if (($val->finger_print_id == $finger_print_id && $val->date == $date && $val->in_time != null)) {
                $temp['status'] = true;
                $temp['shift_name'] = $val->shift_name;
                $temp['inout_status'] = $val->inout_status;
                return $temp;
            }
        }
        return $temp;
    }
    public function hasCompOff($attendance, $finger_print_id, $date, $compOffapplied)
    {
        $temp = [];
        $temp['status'] = false;
        $temp['shift_name'] = '';
        $temp['inout_status'] = '';
        // dump($attendance, $finger_print_id, $date);
        foreach ($attendance as $key => $val) {
            if (($val->finger_print_id == $finger_print_id && $val->date == $date)) {
                if ($compOffapplied != '' || $compOffapplied != null) {
                    $temp['status'] = true;
                    $temp['shift_name'] = $val->shift_name;
                    $temp['inout_status'] = $val->inout_status;

                    return $temp;
                }
            }
        }
        return $temp;
    }

    public function hasOnDuty($onDuty, $employee_id, $date)
    {
        $onDutyRecord = [];
        $temp = [];
        foreach ($onDuty as $value) {

            if ($employee_id == $value->employee_id) {
                $start_date = $value->application_from_date;
                $end_date = $value->application_to_date;
                while (strtotime($start_date) <= strtotime($end_date)) {
                    $temp['employee_id'] = $employee_id;
                    $temp['date'] = $start_date;
                    $onDutyRecord[] = $temp;
                    $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
                }
            }
        }

        foreach ($onDutyRecord as $val) {

            if (($val['employee_id'] == $employee_id && $val['date'] == $date)) {
                return true;
            }
        }

        return false;
    }

    public function ifEmployeeWasLeave($leave, $employee_id, $date)
    {
        $leaveRecord = [];
        $temp = [];
        foreach ($leave as $value) {
            if ($employee_id == $value->employee_id) {
                $start_date = $value->application_from_date;
                $end_date = $value->application_to_date;
                while (strtotime($start_date) <= strtotime($end_date)) {
                    $temp['employee_id'] = $employee_id;
                    $temp['date'] = $start_date;
                    $temp['leave_type_name'] = $value->leave_type_name;
                    $leaveRecord[] = $temp;
                    $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
                }
            }
        }

        foreach ($leaveRecord as $val) {

            if (($val['employee_id'] == $employee_id && $val['date'] == $date)) {
                return $val['leave_type_name'];
            }
        }

        return false;
    }

    public function ifHoliday($govtHolidays, $date, $employee_id, $weeklyHolidays)
    {

        $govt_holidays = [];
        $result = [];
        $result['govt_holiday'] = false;
        $result['weekly_holiday'] = false;

        foreach ($govtHolidays as $holidays) {
            $start_date = $holidays->from_date;
            $end_date = $holidays->to_date;
            while (strtotime($start_date) <= strtotime($end_date)) {
                $govt_holidays[] = $start_date;
                $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
            }
        }

        foreach ($govt_holidays as $val) {
            if ($val == $date) {
                $result['govt_holiday'] = true;
            }
        }



        if (count($weeklyHolidays) > 0) {
            $timestamp = strtotime($date);
            $dayName = date("l", $timestamp);
            foreach ($weeklyHolidays as $v) {
                if ($v->day_name == $dayName && $v->employee_id == $employee_id) {
                    $result['weekly_holiday'] = true;
                    return $result;
                }
            }
        }

        return $result;
    }
}

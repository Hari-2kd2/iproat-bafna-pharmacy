<?php

namespace App\Http\Controllers\Attendance;

use App\Exports\MonthlyAttendanceReportExport;
use App\Exports\SummaryAttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\View\EmployeeAttendaceController;
use App\Lib\Enumerations\UserStatus;
use App\Model\Branch;
use App\Model\Department;
use App\Model\Employee;
use App\Model\LeaveType;
use App\Model\ManualAttendance;
use App\Model\MsSql;
use App\Model\PrintHeadSetting;
use App\Repositories\AttendanceRepository;
use Carbon\CarbonPeriod;
use DateTime;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReportController extends Controller
{

    protected $attendanceRepository;
    protected $employeeAttendaceController;

    public function __construct(AttendanceRepository $attendanceRepository, EmployeeAttendaceController $employeeAttendaceController)
    {
        $this->attendanceRepository = $attendanceRepository;
        $this->employeeAttendaceController = $employeeAttendaceController;
    }

    public function dailyAttendance(Request $request)
    {
        \set_time_limit(0);

        if ((session('logged_session_data.role_id')) == 1 || (session('logged_session_data.role_id')) == 2) {
            $departmentList = Department::get();
            $branchList = Branch::get();
        } else {
            $departmentList = Department::where('department_id', session('logged_session_data.department_id'))->get();
            $branchList = Branch::where('branch_id', session('logged_session_data.branch_id'))->get();
        }

        $results = [];

        if ($_POST) {
            $dailyAttendance = $this->attendanceRepository->getEmployeeDailyAttendance($request->date, $request->department_id, $request->attendance_status, $request->branch_name);
            if ((session('logged_session_data.role_id')) == 1 || (session('logged_session_data.role_id')) == 2) {
                $results = $dailyAttendance;
            } else {
                $departmentName = $departmentList->filter(function ($department) {
                    if ($department->department_id == session('logged_session_data.department_id')) {
                        return $department;
                    }
                })->values()->first();
                $departmentName = $departmentName->department_name;

                $branchName = $branchList->filter(function ($branch) {
                    if ($branch->branch_id == session('logged_session_data.branch_id')) {
                        return $branch;
                    }
                })->values()->first();

                $branchName = $branchName->branch_name;

                foreach ($dailyAttendance as $key => $value) {
                    if (($value->supervisor_id == session('logged_session_data.employee_id') || $value->department_name == $departmentName) && $value->branch_name == $branchName) {
                        $results[] = $value;
                    }
                }
            }
        }
        return view('admin.attendance.report.dailyAttendance', ['results' => json_decode(json_encode($results)), 'departmentList' => $departmentList, 'branchList' => $branchList, 'date' => $request->date, 'department_id' => $request->department_id, 'branch_name' => $request->branch_name, 'attendance_status' => $request->attendance_status]);
    }

    public function monthlyAttendance(Request $request)
    {
        set_time_limit(0);

        if (session('logged_session_data.role_id') == 1 || session('logged_session_data.role_id') == 2) {
            $employeeList = Employee::get();
        } else {
            $employeeList = Employee::where([['department_id', session('logged_session_data.department_id')], ['branch_id', session('logged_session_data.branch_id')]])->get();
        }

        $results = [];

        if ($_POST) {
            $results = $this->attendanceRepository->getEmployeeMonthlyAttendance(dateConvertFormtoDB($request->from_date), dateConvertFormtoDB($request->to_date), $request->employee_id);
        }

        return view('admin.attendance.report.monthlyAttendance', ['results' => $results, 'employeeList' => $employeeList, 'from_date' => $request->from_date, 'to_date' => $request->to_date, 'employee_id' => $request->employee_id]);
    }

    public function myAttendanceReport(Request $request)
    {
        set_time_limit(0);

        $employeeList = Employee::where('status', UserStatus::$ACTIVE)->where('employee_id', session('logged_session_data.employee_id'))->get();
        $results = [];
        if ($_POST) {
            $results = $this->attendanceRepository->getEmployeeMonthlyAttendance(dateConvertFormtoDB($request->from_date), dateConvertFormtoDB($request->to_date), (session('logged_session_data.employee_id')));
        } else {
            $results = $this->attendanceRepository->getEmployeeMonthlyAttendance(date('Y-m-01'), date("Y-m-t", strtotime(date('Y-m-01'))), (session('logged_session_data.employee_id')));
        }

        return view('admin.attendance.report.mySummaryReport', ['results' => $results, 'employeeList' => $employeeList, 'from_date' => $request->from_date, 'to_date' => $request->to_date, 'employee_id' => $request->employee_id]);
    }

    public function attendanceSummaryReport(Request $request)
    {
        set_time_limit(0);

        if ((session('logged_session_data.role_id')) == 1 || (session('logged_session_data.role_id')) == 2) {
            $departmentList = Department::get();
            $branchList = Branch::get();
        } else {
            $departmentList = Department::where('department_id', session('logged_session_data.department_id'))->get();
            $branchList = Branch::where('branch_id', session('logged_session_data.branch_id'))->get();
        }

        if ($request->from_date && $request->to_date) {
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            // dd(dateConvertFormtoDB($from_date), dateConvertFormtoDB($to_date));
        } else {
            $from_date = date("01/m/Y");
            $to_date = date("t/m/Y");
        }
        $result = [];
        $month = date('Y-m', strtotime(dateConvertFormtoDB($from_date)));
        $monthAndYear = explode('-', $month);
        $month_data = $monthAndYear[1];
        $dateObj = DateTime::createFromFormat('!m', $month_data);
        $monthName = $dateObj->format('F');

        $monthToDate = findFromDateToDateToAllDate(dateConvertFormtoDB($from_date), dateConvertFormtoDB($to_date));
        $leaveType = LeaveType::get();
        if ($_POST) {
            $result = $this->attendanceRepository->findAttendanceSummaryReport($month, dateConvertFormtoDB($from_date), dateConvertFormtoDB($to_date), $request->branch_name, $request->department_name);
        }

        return view('admin.attendance.report.summaryReport', ['results' => $result, 'monthToDate' => $monthToDate, 'month' => $month, 'from_date' => $request->from_date, 'to_date' => $request->to_date, 'leaveTypes' => $leaveType, 'monthName' => $monthName, 'branchList' => $branchList, 'branch_name' => $request->branch_name, 'departmentList' => $departmentList, 'department_name' => $request->department_name]);
    }

    // public function downloadAttendanceSummaryReport($from_date, $to_date)
    // {
    //     $printHead = PrintHeadSetting::first();
    //     $month = date('Y-m', strtotime($from_date));
    //     $monthToDate = findMonthToAllDate($month);
    //     $leaveType = LeaveType::get();
    //     $result = $this->attendanceRepository->findAttendanceSummaryReport($month, $from_date, $to_date);

    //     $monthAndYear = explode('-', $month);
    //     $month_data = $monthAndYear[1];
    //     $dateObj = DateTime::createFromFormat('!m', $month_data);
    //     $monthName = $dateObj->format('F');

    //     $data = [
    //         'results' => $result,
    //         'month' => $month,
    //         'printHead' => $printHead,
    //         'monthToDate' => $monthToDate,
    //         'leaveTypes' => $leaveType,
    //         'monthName' => $monthName,
    //     ];
    //     $pdf = PDF::loadView('admin.attendance.report.pdf.attendanceSummaryReportPdf', $data);
    //     $pdf->setPaper('A4', 'landscape');
    //     return $pdf->download("attendance-summaryReport.pdf");
    // }

    public function monthlyExcel(Request $request)
    {
        \set_time_limit(0);

        $employeeList = Employee::get();
        $employeeInfo = Employee::with('department')->where('employee_id', $request->employee_id)->first();
        $printHead = PrintHeadSetting::first();
        $results = [];

        if ($request->from_date && $request->to_date && $request->employee_id) {
            $results = $this->attendanceRepository->getEmployeeMonthlyAttendance(dateConvertFormtoDB($request->from_date), dateConvertFormtoDB($request->to_date), $request->employee_id);
        }

        $excel = new MonthlyAttendanceReportExport('admin.attendance.report.monthlyAttendancePagination', [
            'printHead' => $printHead, 'employeeInfo' => $employeeInfo, 'results' => $results, 'employeeList' => $employeeList,
            'from_date' => $request->from_date, 'to_date' => $request->to_date, 'employee_id' => $request->employee_id,
            'employee_name' => $employeeInfo->first_name . ' ' . $employeeInfo->last_name,
            'department_name' => $employeeInfo->department->department_name,
        ]);

        $excelFile = Excel::download($excel, 'monthlyReport.xlsx');

        return $excelFile;
    }
    public function summaryExcel(Request $request)
    {
        \set_time_limit(0);

        $monthToDate = findMonthToAllDate($request->month);
        $leaveType = LeaveType::get();
        $start_date = $request->month . '-01';
        $end_date = date("Y-m-t", strtotime($start_date));
        $result = $this->attendanceRepository->findAttendanceSummaryReport($request->month, $start_date, $end_date);
        $employeeInfo = Employee::with('department')->where('employee_id', $request->employee_id)->first();
        $monthAndYear = explode('-', $request->month);
        $month_data = $monthAndYear[1];
        $dateObj = DateTime::createFromFormat('!m', $month_data);
        $monthName = $dateObj->format('F');

        $data = [
            'results' => $result,
            'month' => $request->month,
            'monthToDate' => $monthToDate,
            'leaveTypes' => $leaveType,
            'monthName' => $monthName,
        ];

        $excel = new SummaryAttendanceReportExport('admin.attendance.report.summaryReportPagination', $data);

        $excelFile = Excel::download($excel, 'summaryReport' . date('Ym', strtotime($request->month)) . date('His') . '.xlsx');

        return $excelFile;
    }

    public function attendanceRecord(Request $request)
    {
        set_time_limit(0);
        $results = $employee = [];

        if ($_POST) {
            $from_date = dateConvertFormtoDB($request->from_date);
            $to_date = dateConvertFormtoDB($request->to_date);

            if (session('logged_session_data.role_id') != 1 && session('logged_session_data.role_id') != 2) {
                $employee = Employee::where('branch_id', session('logged_session_data.branch_id'))->where('department_id', session('logged_session_data.department_id'))->pluck('finger_id')->toArray();
            } else {
                $employee = Employee::pluck('finger_id')->toArray();
            }

            if ($request->from_date && $request->to_date) {
                $ms_sql = MsSql::whereDate('datetime', '>=', $from_date)->whereDate('datetime', '<=', $to_date)
                    ->with(['employee' => function ($q) {
                        $q->with('department', 'designation', 'branch');
                    }]);
                if (session('logged_session_data.role_id') != 1 && session('logged_session_data.role_id') != 2) {
                    $ms_sql = $ms_sql->whereIn('ID', $employee)->get();
                } else {
                    $ms_sql = $ms_sql->get();
                }

                $ms_sql->transform(function ($item) {
                    $item->device_name = 'FRT Device';
                    return $item;
                });

                $results = collect($ms_sql);

                $manual_attendance = ManualAttendance::whereDate('datetime', '>=', $from_date)->whereDate('datetime', '<=', $to_date)
                    ->with(['employee' => function ($q) {
                        $q->with('department', 'designation', 'branch');
                    }]);

                if (session('logged_session_data.role_id') != 1 && session('logged_session_data.role_id') != 2) {
                    $manual_attendance = $manual_attendance->whereIn('ID', $employee)->get();
                } else {
                    $manual_attendance = $manual_attendance->get();
                }

                $manual_attendance->transform(function ($item) {
                    $item->device_name = 'Manual Attendance';
                    return $item;
                });

                $results = $results->merge($manual_attendance)->toArray();

            }
        }

        return \view('admin.attendance.report.attendanceRecord', ['results' => $results, 'device_name' => $request->device_name, 'from_date' => $request->from_date, 'to_date' => $request->to_date, 'employee_id ' => $request->employee_id, 'branch_id ' => $request->employee_id]);
    }

    public function report(Request $request)
    {
        return view('admin.attendance.calculateAttendance.index');
    }

    public function calculateReport(Request $request)
    {

        $dates = CarbonPeriod::create(dateConvertFormtoDB($request->from_date), dateConvertFormtoDB($request->to_date))->toArray();

        $this->employeeAttendaceController->attendance(null, false, null, $dates);

        return redirect()->back()->with('success', 'reports generated successfully');
    }
}

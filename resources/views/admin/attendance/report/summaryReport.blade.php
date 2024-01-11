@extends('admin.master')
@section('content')
@section('title')
    @lang('attendance.attendance_summary_report')
@endsection
<style>
    .present {
        color: #7ace4c;
        font-weight: 700;
        cursor: pointer;
    }

    .absence {
        color: #f33155;
        font-weight: 700;
        cursor: pointer;
    }

    .leave {
        color: #41b3f9;
        font-weight: 700;
        cursor: pointer;
    }

    .bolt {
        font-weight: 700;
    }
</style>
<script>
    jQuery(function() {
        $("#attendanceSummaryReport").validate();
    });
</script>
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
            <ol class="breadcrumb">
                <li class="active breadcrumbColor"><a href="{{ url('dashboard') }}"><i class="fa fa-home"></i>
                        @lang('dashboard.dashboard')</a></li>
                <li>@yield('title')</li>
            </ol>
        </div>

    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-info">
                <div class="panel-heading"><i class="mdi mdi-table fa-fw"></i>@yield('title')</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        <div class="row">
                            <div id="searchBox">
                                {{ Form::open([
                                    'route' => 'attendanceSummaryReport.attendanceSummaryReport',
                                    'id' => 'attendanceSummaryReport',
                                ]) }}

                                @if (session('logged_session_data.role_id') == 1 || session('logged_session_data.role_id') == 2)
                                    <div class="col-md-2"></div>
                                @else
                                    <div class="col-md-3"></div>
                                @endif

                                @if (session('logged_session_data.role_id') == 1 || session('logged_session_data.role_id') == 2)
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="branch_name">@lang('common.branch'):</label>
                                            <select name="branch_name" class="form-control branch_name  select2">
                                                <option value="">--- @lang('common.all') ---</option>
                                                @foreach ($branchList as $value)
                                                    <option value="{{ $value->branch_name }}"
                                                        @if ($value->branch_name == $branch_name) {{ 'selected' }} @endif>
                                                        {{ $value->branch_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                @if (session('logged_session_data.role_id') == 1 || session('logged_session_data.role_id') == 2)
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label"
                                                for="department_name">@lang('common.department'):</label>
                                            <select name="department_name"
                                                class="form-control department_name  select2">
                                                <option value="">--- @lang('common.all') ---</option>
                                                @foreach ($departmentList as $value)
                                                    <option value="{{ $value->department_name }}"
                                                        @if ($value->department_name == $department_name) {{ 'selected' }} @endif>
                                                        {{ $value->department_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-2">
                                    <label class="control-label" for="email">@lang('common.from_date')<span
                                            class="validateRq">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="text" class="form-control dateField required" readonly
                                            placeholder="@lang('common.from_date')" name="from_date"
                                            value="@if (isset($from_date)) {{ $from_date }}@else {{ dateConvertDBtoForm(date('Y-m-01')) }} @endif">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <label class="control-label" for="email">@lang('common.to_date')<span
                                            class="validateRq">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="text" class="form-control dateField required" readonly
                                            placeholder="@lang('common.to_date')" name="to_date"
                                            value="@if (isset($to_date)) {{ $to_date }}@else {{ dateConvertDBtoForm(date('Y-m-t', strtotime(date('Y-m-01')))) }} @endif">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <input type="submit" id="filter" style="margin-top: 26px;"
                                            class="btn btn-info btn-md" value="@lang('common.filter')">
                                    </div>
                                </div>
                                {{ Form::close() }}
                            </div>
                        </div>
                        <br>

                        <div class="table-responsive">
                            <table id="myDataTable" class="table table-bordered table-striped table-hover"
                                style="font-size: 12px">
                                <thead>
                                    <tr class="tr_header">
                                        <th>@lang('common.serial')</th>
                                        <th>@lang('employee.employee_id')</th>
                                        <th>@lang('common.name')</th>
                                        <th>@lang('employee.designation')</th>
                                        <th>@lang('employee.branch')</th>
                                        <th>@lang('employee.department')</th>
                                        <th>@lang('employee.gender')</th>
                                        <th>@lang('employee.status')</th>
                                        @foreach ($monthToDate as $head)
                                            <th class="text-center">{{ $head['day'] . ' ' . $head['day_name'] }}</th>
                                        @endforeach

                                        <th>@lang('attendance.day_of_worked')</th>
                                        <th>@lang('attendance.public_holiday')</th>
                                        @foreach ($leaveTypes as $leaveType)
                                            <th>{{ $leaveType->leave_type_name }}</th>
                                        @endforeach
                                        <th>@lang('attendance.total_paid_days')</th>
                                        <th>@lang('attendance.weekly_holiday') </th>
                                        <th>@lang('attendance.total_days')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sl = null;
                                        $totalPresent = 0;
                                        $leaveData = [];
                                        $totalCol = 0;
                                        $totalWorkHour = 0;
                                        $totalWeeklyHoliday = 0;
                                        $totalGovtHoliday = 0;
                                        $totalAbsent = 0;
                                        $totalLeave = 0;
                                    @endphp
                                    @foreach ($results as $key => $value)
                                        <tr>
                                            <td>{{ ++$sl }}</td>
                                            <td>{{ $value[0]['finger_id'] }}</td>
                                            <td>{{ $value[0]['fullName'] }}</td>
                                            <td>{{ $value[0]['designation_name'] }}</td>
                                            <td>{{ $value[0]['branch_name'] }}</td>
                                            <td>{{ $value[0]['department_name'] }}</td>
                                            <td>{{ $value[0]['gender'] }}</td>
                                            <td>{{ userStatus($value[0]['status']) }}</td>
                                            @foreach ($value as $v)
                                                @php
                                                    if ($sl == 1) {
                                                        $totalCol++;
                                                    }

                                                    if ($v['attendance_status'] == 'present') {
                                                        $totalPresent++;
                                                        if ($v['shift_name'] != '' && $v['shift_name'] != null) {
                                                            // $shiftName = $v['shift_name'];
                                                            $shiftName = 'P';
                                                        } else {
                                                            $shiftName = 'P';
                                                        }

                                                        if ($v['inout_status'] == 'O') {
                                                            echo "<td><span style='color:red ;font-weight:bold'>" . $v['inout_status'] . '' . $shiftName . '</span></td>';
                                                        } else {
                                                            echo "<td><span style='color:#7ace4c ;font-weight:bold'>" . $shiftName . '</span></td>';
                                                        }
                                                    } elseif ($v['attendance_status'] == 'CompOff') {
                                                        $totalPresent++;
                                                        echo "<td><span style='color:#41b3f9 ;font-weight:bold'>CO</span></td>";
                                                    } elseif ($v['attendance_status'] == 'OnDuty') {
                                                        $totalPresent++;
                                                        echo "<td><span style='color:#41b3f9 ;font-weight:bold'>OD</span></td>";
                                                    } elseif ($v['attendance_status'] == 'absence') {
                                                        $totalAbsent++;
                                                        echo "<td><span style='color:#000000 ;font-weight:bold'>A</span></td>";
                                                    } elseif ($v['attendance_status'] == 'leave') {
                                                        $totalLeave++;
                                                        $leaveData[$key][$v['leave_type']][] = $v['leave_type'];
                                                        echo "<td><span style='color:#41b3f9 ;font-weight:bold'>" . stringFirstLetter($v['leave_type']) ?? 'NA' . '</span></td>';
                                                    } elseif ($v['attendance_status'] == 'holiday') {
                                                        $totalWeeklyHoliday++;
                                                        echo "<td><span style='color:turquoise ;font-weight:bold'>WH</span></td>";
                                                    } elseif ($v['attendance_status'] == 'publicHoliday') {
                                                        $totalGovtHoliday++;
                                                        echo "<td><span style='color: turquoise ;font-weight:bold'>PH</span></td>";
                                                    } elseif ($v['attendance_status'] == 'left') {
                                                        echo "<td><span style='color:#000000 ;font-weight:bold'></span></td>";
                                                    } else {
                                                        echo '<td></td>';
                                                    }
                                                @endphp
                                            @endforeach
                                            <td><span class="bolt">{{ $totalPresent }}</span></td>
                                            <td><span class="bolt">{{ $totalGovtHoliday }}</span></td>

                                            @foreach ($leaveTypes as $leaveType)
                                                <td>
                                                    <span class="bolt">
                                                        @php
                                                            if ($sl == 1) {
                                                                $totalCol++;
                                                            }
                                                            if (isset($leaveData[$key][$leaveType->leave_type_name])) {
                                                                $c = count($leaveData[$key][$leaveType->leave_type_name]);
                                                            } else {
                                                                $c = 0;
                                                            }
                                                        @endphp
                                                        {{ $c }}
                                                    </span>
                                                </td>
                                            @endforeach
                                            <td><span
                                                    class="bolt">{{ $totalPresent + $totalLeave + $totalGovtHoliday }}</span>
                                            </td>
                                            <td><span class="bolt">{{ $totalWeeklyHoliday }}</span></td>
                                            <td><span
                                                    class="bolt">{{ $totalPresent + $totalWeeklyHoliday + $totalAbsent + $totalLeave }}</span>
                                            </td>
                                            @php
                                                $totalPresent = 0;
                                                $totalWeeklyHoliday = 0;
                                                $totalAbsent = 0;
                                                $totalLeave = 0;
                                                $totalGovtHoliday = 0;
                                            @endphp
                                        </tr>
                                    @endforeach
                                    <script>
                                        // {!! "$('.totalCol').attr('colspan',$totalCol+3);" !!}
                                    </script>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

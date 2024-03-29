@extends('admin.master')
@section('content')
@section('title')
    @lang('attendance.my_attendance_report')
@endsection
<style>
    .employeeName {
        position: relative;
    }

    #employee_id-error {
        position: absolute;
        top: 66px;
        left: 0;
        width: 100%he;
        width: 100%;
        height: 100%;
    }

    /*
  tbody {
   display:block;
   height:500px;
   overflow:auto;
  }
  thead, tbody tr {
   display:table;
   width:100%;
   table-layout:fixed;
  }
  thead {
   width: calc( 100% - 1em )
  }*/
</style>
<script>
    jQuery(function() {
        $("#monthlyAttendance").validate();
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
                                {{ Form::open(['route' => 'myAttendanceReport.myAttendanceReport', 'id' => 'monthlyAttendance']) }}
                                <div class="col-md-2"></div>
                                <div class="col-md-2">
                                    <div class="form-group employeeName">
                                        <label class="control-label" for="email">@lang('common.employee')<span
                                                class="validateRq">*</span></label>
                                        <select class="form-control employee_id select2 required" required
                                            name="employee_id">
                                            @foreach ($employeeList as $value)
                                                <option value="{{ $value->employee_id }}">{{ $value->first_name }}
                                                    {{ $value->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
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
                        <hr>
                        <h4 class="text-right" hidden>
                            @if (isset($from_date))
                                @if (count($results) > 0)
                                    <a class="btn btn-success" style="color: #fff"
                                        href="{{ URL('downloadMyAttendance/?employee_id=' . $employee_id . '&from_date=' . $from_date . '&to_date=' . $to_date) }}"><i
                                            class="fa fa-download fa-lg" aria-hidden="true"></i> @lang('common.download')
                                        PDF</a>
                                @endif
                            @else
                                @if (count($results) > 0)
                                    <a class="btn btn-success" style="color: #fff"
                                        href="{{ URL('downloadMyAttendance/?employee_id=' . (session('logged_session_data.employee_id')) . '&from_date=' . dateConvertDBtoForm(date('Y-m-01')) . '&to_date=' . dateConvertDBtoForm(date('Y-m-t', strtotime(date('Y-m-01'))))) }}"><i
                                            class="fa fa-download fa-lg" aria-hidden="true"></i> @lang('common.download')
                                        PDF</a>
                                @endif
                            @endif
                        </h4>
                        <div class="table-responsive">
                            <table id="myDataTable" class="table table-bordered" style="font-size: 12px">
                                <thead class="tr_header">
                                    <tr>
                                        <th style="width:100px;">@lang('common.serial')</th>
                                        <th>@lang('common.date')</th>
                                        <th>@lang('attendance.in_time')</th>
                                        <th>@lang('attendance.out_time')</th>
                                        <th>@lang('attendance.working_time')</th>
                                        <th>@lang('attendance.total_duration')</th>
                                        <th>@lang('common.status')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($results) > 0)
                                        <?php
                                        $totalPresent = 0;
                                        $totalHoliday = 0;
                                        $totalAbsence = 0;
                                        $totalLeave = 0;
                                        $totalLate = 0;
                                        $totalHour = 0;
                                        $totalMinit = 0;
                                        $total_working_hour = 0;
                                        // dd($results);
                                        ?>

                                        {{ $serial = null }}
                                        @foreach ($results as $value)
                                            <tr>
                                                <td style="width:100px;">{{ ++$serial }}</td>
                                                <td>{{ $value['date'] }}</td>
                                                <td>

                                                    @if ($value['in_time'] != '')
                                                        {{ $value['in_time'] }}
                                                    @else
                                                        {{ '--' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($value['out_time'] != '')
                                                        {{ $value['out_time'] }}
                                                    @else
                                                        {{ '--' }}
                                                    @endif
                                                </td>

                                                <td>
                                                    @if($value['comp_off_data'] != '') 
                                                            {{$value['comp_off_data'] }}
                                                    @endif                                                   
                                                            @if($value['permission_status'] == 1 )
                                                                {{'Permission:'.date('H:i', strtotime($value['permission_duration']))}}  
                                                            @endif
                                                </td>
                                                <td>  
                                                    @if(isset($value['totalduration']) && $value['totalduration'] != '00:00') 
                                                            {{date('H:i', strtotime($value['totalduration']))  }}
                                                    @elseif($value['totalduration'] == '00:00')
                                                    {{''}}
                                                    @endif      
                                                </td>
                                                <td>
                                                @if ($value['comp_off_data'] != '')
                                                        <span class='label label-success'> {{ __('common.present') }}
                                                        </span>
                                                        <?php $totalPresent += 1; ?>
                                                    @elseif ($value['action'] == 'Absence')
                                                        <span class='label label-danger'> {{ __('common.absence') }}
                                                        </span>
                                                        <?php $totalAbsence += 1; ?>
                                                    @elseif ($value['action'] == 'Leave')
                                                        <span class='label label-warning'> {{ __('common.leave') }}
                                                        </span></p>
                                                        <?php $totalLeave += 1; ?>
                                                    @elseif ($value['action'] == 'Holiday')
                                                        <span class='label label-info'>{{ 'Holiday' }}</span>
                                                        </p>
                                                        <?php $totalHoliday += 1; ?>
                                                    @elseif($value['action'] == 'Present' && $value['comp_off_data'] != '')
                                                        <span
                                                            class='label label-success'>{{ __('common.present') }}</span>
                                                        <?php $totalPresent += 1; ?>
                                                    @else
                                                    <span class='label label-danger'> {{ __('common.absence') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <?php
                                                $totalPresent = $value['total_present'];
                                                $total_working_hour = ($totalHour * 60 + $totalMinit) / 60;
                                                ?>
                                            </tr>
                                        @endforeach
                                        {{-- <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td style="background: #eee"><b>@lang('attendance.total_working_days'): &nbsp;</b></td>
                                            <td style="background: #eee"><b>{{ $serial }}</b>
                                                @lang('common.days')
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td style="background: #fff"><b>@lang('attendance.total_present'): &nbsp;</b></td>
                                            <td style="background: #fff"><b>{{ round($totalPresent) }}</b>
                                                @lang('common.days')
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td style="background: #eee"><b>Total Holiday: &nbsp;</b></td>
                                            <td style="background: #eee"><b>{{ round($totalHoliday) }}</b>
                                                @lang('common.days')
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td style="background: #fff"><b>Total Leave: &nbsp;</b></td>
                                            <td style="background: #fff"><b>{{ round($totalLeave) }}</b>
                                                @lang('common.days')
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td style="background: #eee"><b>Total Absent: &nbsp;</b></td>
                                            <td style="background: #eee"><b>{{ round($totalAbsence) }}</b>
                                                @lang('common.days')</td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td style="background: #fff"><b>@lang('attendance.actual_working_hour'): &nbsp;</b></td>
                                            <td style="background: #fff"><b>{{ round($total_working_hour) }}</b>
                                                @lang('common.hours')</td>
                                        </tr> --}}
                                    @endif
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

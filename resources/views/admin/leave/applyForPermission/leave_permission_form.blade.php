@extends('admin.master')
@section('content')
@section('title')
    @lang('leave.leave_permission_form')
@endsection
<style>
    .datepicker table tr td.disabled,
    .datepicker table tr td.disabled:hover {
        background: none;
        color: red !important;
        cursor: default;
    }

    td {
        color: black !important;
    }
</style>

<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-5 col-md-5 col-sm-5 col-xs-12">
            <ol class="breadcrumb">
                <li class="active breadcrumbColor"><a href="#"><i class="fa fa-home"></i>
                        @lang('dashboard.dashboard')</a></li>
                <li>@yield('title')</li>

            </ol>
        </div>
        <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
            <a href="{{ route('applyForPermission.index') }}"
                class="btn btn-success pull-right m-l-20 hidden-xs hidden-sm waves-effect waves-light"><i
                    class="fa fa-list-ul" aria-hidden="true"></i> @lang('leave.view_leave_permission')</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading"><i class="mdi mdi-clipboard-text fa-fw"></i>@lang('leave.leave_permission_form')</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">

                    <div class="panel-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">×</span></button>
                                @foreach ($errors->all() as $error)
                                    <strong>{!! $error !!}</strong><br>
                                @endforeach
                            </div>
                        @endif
                        @if (session()->has('success'))
                            <div class="alert alert-success alert-dismissable">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <i
                                    class="cr-icon glyphicon glyphicon-ok"></i>&nbsp;<strong>{{ session()->get('success') }}</strong>
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger alert-dismissable">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <strong>{{ session()->get('error') }}</strong>
                            </div>
                        @endif

                        @if (Auth::user()->role_id != 1)
                            <div class="row" style="background: #F8F8F8;padding:12px;margin:0 2px">
                                <div class="col-md-1"></div>
                                <div class="col-md-3"><b>Total Permissions Per Month : {{ '1' }} </b></div>
                                <div class="col-md-2"><b>Applied Permission : {{ $appliedpermissions }} </b></div>
                                <div class="col-md-2"><b>Approved : {{ $takenPermissions }} </b></div>
                                <div class="col-md-2"><b>Balance : {{ 1 - $takenPermissions }} </b></div>
                                <div class="col-md-2"></div>
                            </div>
                            <br>
                        @endif

                        {{ Form::open(['route' => 'applyForPermission.store', 'id' => 'leavePermissionForm']) }}
                        <div class="form-body">
                            <div class="row">

                                {!! Form::hidden(
                                    'employee_id',
                                    isset($getEmployeeInfo) ? $getEmployeeInfo->employee_id : '',
                                    $attributes = ['class' => 'employee_id'],
                                ) !!}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInput">@lang('common.employee_name')<span
                                                class="validateRq">*</span></label>
                                        {!! Form::text(
                                            '',
                                            isset($getEmployeeInfo) ? $getEmployeeInfo->first_name . ' ' . $getEmployeeInfo->last_name : '',
                                            $attributes = ['class' => 'form-control', 'readonly' => 'readonly'],
                                        ) !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="exampleInput">@lang('common.date')<span class="validateRq">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        {!! Form::text(
                                            'permission_date',
                                            old('permission_date'),
                                            $attributes = [
                                                'class' => 'form-control permission_date required',
                                                'readonly' => 'readonly',
                                                'placeholder' => __('common.permission_date'),
                                            ],
                                        ) !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInput">@lang('leave.total_permission_taken')<small> (Monthly in Hrs)</small><span
                                                class="validateRq">*</span></label>
                                        {!! Form::text(
                                            '',
                                            '',
                                            $attributes = [
                                                'class' => 'form-control current_balance required',
                                                'readonly' => 'readonly',
                                                'placeholder' => __('leave.applied_permission_count'),
                                            ],
                                        ) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInput">@lang('leave.total_permission_count') <small> (Monthly in
                                                Nos)</small><span class="validateRq">*</span></label>
                                        {!! Form::text(
                                            '',
                                            '',
                                            $attributes = [
                                                'class' => 'form-control total_permission_count required',
                                                'readonly' => 'readonly',
                                                'placeholder' => __('leave.total_permission_count'),
                                            ],
                                        ) !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group bootstrap-timepicker">
                                        <label for="exampleInput">@lang('leave.permission_from_time')<span
                                                class="validateRq">*</span></label>
                                        <input class="form-control timepicker-from required"
                                            onChange="findTimeDifference()" type="text"
                                            placeholder="@lang('leave.permission_from_time')" name="from_time" id="from_time" readonly>

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group bootstrap-timepicker">
                                        <label for="exampleInput">@lang('leave.permission_to_time') <span
                                                class="validateRq">*</span></label>
                                        <input class="form-control timepicker-to required"
                                            onChange="findTimeDifference()" type="text"
                                            placeholder="@lang('leave.permission_to_time')" name="to_time" id="to_time" readonly>

                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInput">@lang('leave.permission_duration') <small>(in Hrs)</small><span
                                                class="validateRq">*</span></label>
                                        {!! Form::text(
                                            'permission_duration',
                                            old('permission_duration'),
                                            $attributes = [
                                                'class' => 'form-control permission_duration required',
                                                'readonly' => 'readonly',
                                                'min' => '00:00',
                                                'max' => '02:00',
                                                'placeholder' => __('common.permission_duration'),
                                            ],
                                        ) !!}
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInput">@lang('leave.purpose')<span
                                                class="validateRq">*</span></label>
                                        {!! Form::textarea(
                                            'purpose',
                                            old('purpose'),
                                            $attributes = [
                                                'class' => 'form-control purpose required',
                                                'id' => 'purpose',
                                                'placeholder' => __('leave.purpose'),
                                                'cols' => '30',
                                                'rows' => '3',
                                            ],
                                        ) !!}
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" id="formSubmit" class="btn btn-info "><i
                                            class="fa fa-paper-plane"></i> @lang('leave.send_application')</button>
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page_scripts')
<script>
    jQuery(function() {
        $(document).on("focus", ".permission_date", function() {
            $(this).datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                clearBtn: true,
                // startDate: new Date(),
            }).on('changeDate', function(e) {
                $(this).datepicker('hide');
            });
        });
    });

    var minuteStep = 1;
    var maxHours = 1;
    var maxMinutes = 1;
    var monthlyLimit = 0;
    var permissionTaken = 0;
    var takenHours = 0;
    var takenMinutes = 0;
    var totalHours = 0;
    var totalMinutes = 0;
    var totalCount = 0;

    $(document).ready(function() {
        $.ajax({
            type: "get",
            url: "{{ route('permissionMaster.index') }}",
            dataType: "json",
            success: function(response) {
                minuteStep = response.min;
                maxHours = response.max_hours;
                maxMinutes = response.max_minutes;
                totalHours = response.total_hours;
                totalMinutes = response.total_minutes;
                monthlyLimit = response.monthly_limit;
            },
            error: function(response) {
                //
            }
        });
    });

    $(document).on("focus", ".timepicker-from", function() {
        $(this).timepicker({
            showInputs: false,
            showMeridian: false,
            scrollDefaultNow: 'true',
            closeOnWindowScroll: 'true',
            showDuration: false,
            ignoreReadonly: true,
            minuteStep: minuteStep,
        }).on('changeTime.timepicker', function(e) {
            // console.log('The time is ' + e.time.value);
            // console.log('The hour is ' + e.time.hours);
            // console.log('The minute is ' + e.time.minutes);
            // console.log('The meridian is ' + e.time.meridian);
        });
    });

    $(document).on("focus", ".timepicker-to", function() {
        $(this).timepicker({
            showInputs: false,
            showMeridian: false,
            scrollDefaultNow: 'true',
            closeOnWindowScroll: 'true',
            showDuration: false,
            ignoreReadonly: true,
            minuteStep: minuteStep,
        }).on('changeTime.timepicker', function(e) {
            // console.log('The time is ' + e.time.value);
            // console.log('The hour is ' + e.time.hours);
            // console.log('The minute is ' + e.time.minutes);
            // console.log('The meridian is ' + e.time.meridian);

        });
    });

    function findTimeDifference() {
        var valuestart = $('#from_time').val();
        var valuestop = $('#to_time').val();

        if (valuestop != '' && valuestart != '') {

            dt1 = new Date("01/01/2023 " + valuestart);
            dt2 = new Date("01/01/2023 " + valuestop);

            var date1_ms = dt1.getTime();
            var date2_ms = dt2.getTime();

            // Calculate the difference in milliseconds
            if (date2_ms >= date1_ms) {

                var difference_ms = date2_ms - date1_ms;

                //take out milliseconds
                difference_ms = difference_ms / 1000;
                var seconds = Math.floor(difference_ms % 60);
                difference_ms = difference_ms / 60;

                var minutes = Math.floor(difference_ms % 60);
                difference_ms = difference_ms / 60;

                var hours = Math.floor(difference_ms % 24);
                var days = Math.floor(difference_ms / 24);

                $('.permission_duration').val(hours + ':' + minutes);

            } else {
                $.toast({
                    heading: 'Warning',
                    text: 'Invalid time selection',
                    position: 'top-right',
                    loaderBg: '#ff6849',
                    icon: 'warning',
                    hideAfter: 3000,
                    stack: 1
                });
                $('body').find('#formSubmit').attr('disabled', true);
                return;
            }

            var h = parseInt(hours);
            var m = parseInt(minutes);
            var takenH = parseInt(takenHours);
            var takenM = parseInt(takenMinutes);
            var maxH = parseInt(maxHours);
            var maxM = parseInt(maxMinutes);
            var totH = parseInt(totalHours);
            var totM = parseInt(totalMinutes);

            // console.log([h, m, maxH, maxM, takenH, takenM, totH, totM]);
            // console.log([(h * 60 + m), ((maxH * 60) + maxM)]);
            // console.log([((takenH * 60) + takenM), ((totH * 60) + totM)]);

            if ((h * 60 + m) > ((maxH * 60) + maxM) || ((h * 60 + m) + (takenH * 60) + takenM) >= ((totH * 60) +
                totM)) {
                $.toast({
                    heading: 'Warning',
                    text: 'Permission only allowes for maximun ' + maxHours + ' hours ' + maxMinutes +
                        ' minutes and totally ' + totalHours + ' hours ' + totalMinutes + ' minutes.',
                    position: 'top-right',
                    loaderBg: '#ff6849',
                    icon: 'warning',
                    hideAfter: 3000,
                    stack: 1
                });
                $('body').find('#formSubmit').attr('disabled', true);
            } else {
                $('body').find('#formSubmit').attr('disabled', false);
            }
        }
    }

    $(document).on("change", ".permission_date,.employee_id", function() {
        var permission_date = $('.permission_date').val();
        var employee_id = $('.employee_id ').val();
        if (permission_date != '' && employee_id != '') {
            var action = "{{ URL::to('applyForPermission/applyForTotalNumberOfPermissions') }}";
            $.ajax({
                type: 'POST',
                url: action,
                data: {
                    'permission_date': permission_date,
                    'employee_id': employee_id,
                    '_token': $('input[name=_token]').val()
                },
                dataType: 'json',
                success: function(response) {
                    permissionTaken = response.duration;
                    takenMinutes = response.minutes;
                    takenHours = response.hours;
                    totalCount = response.total_count;

                    $('.current_balance').val(permissionTaken ?? '00:00');
                    $('.total_permission_count').val(totalCount ?? 0);

                    if (totalCount > monthlyLimit) {
                        $.toast({
                            heading: 'Warning',
                            text: 'You already applied ' + $('.current_balance').val(),
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'warning',
                            hideAfter: 3000,
                            stack: 1
                        });
                        $('body').find('#formSubmit').attr('disabled', true);
                        $('.current_balance').val(permissionTaken ?? '00:00');
                        $('.total_permission_count').val(totalCount ?? 0);
                    } else {
                        $('.current_balance').val(permissionTaken ?? '00:00');
                        $('body').find('#formSubmit').attr('disabled', false);
                        $('.total_permission_count').val(totalCount ?? 0);
                    }
                },
                error: function(e) {
                    console.log(e);
                }
            });
        } else {
            $('body').find('#formSubmit').attr('disabled', true);
        }
    });
</script>
@endsection

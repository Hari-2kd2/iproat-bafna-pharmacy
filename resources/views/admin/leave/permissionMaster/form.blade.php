@extends('admin.master')
@section('content')

@section('title')
    @if (isset($editModeData))
        @lang('leave.edit_permission_master');
    @else
        @lang('leave.add_permission_master');
    @endif
@endsection


<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <ol class="breadcrumb">
                <li class="active breadcrumbColor"><a href="{{ url('dashboard') }}"><i class="fa fa-home"></i>
                        @lang('dashboard.dashboard')</a></li>
                <li>@yield('title')</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading"><i class="mdi mdi-clipboard-text fa-fw"></i>@yield('title')</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        {{ Form::model($editModeData, ['route' => ['permissionMaster.update', $editModeData->permission_master_id], 'method' => 'PUT', 'files' => 'true', 'id' => 'permissionMasterForm', 'class' => 'form-horizontal']) }}
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-offset-2 col-md-6">
                                    @if ($errors->any())
                                        <div class="alert alert-danger alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert"
                                                aria-label="Close"><span aria-hidden="true">×</span></button>
                                            @foreach ($errors->all() as $error)
                                                <strong>{!! $error !!}</strong><br>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (session()->has('success'))
                                        <div class="alert alert-success alert-dismissable">
                                            <button type="button" class="close" data-dismiss="alert"
                                                aria-hidden="true">×</button>
                                            <i
                                                class="cr-icon glyphicon glyphicon-ok"></i>&nbsp;<strong>{{ session()->get('success') }}</strong>
                                        </div>
                                    @endif
                                    @if (session()->has('error'))
                                        <div class="alert alert-danger alert-dismissable">
                                            <button type="button" class="close" data-dismiss="alert"
                                                aria-hidden="true">×</button>
                                            <i
                                                class="glyphicon glyphicon-remove"></i>&nbsp;<strong>{{ session()->get('error') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="col-md-11">
                                            <div class="form-group bootstrap-timepicker">
                                                <label for="exampleInput">@lang('leave.min_duration')<span
                                                        class="validateRq">*</span></label>
                                                <input class="form-control timePicker required" type="text"
                                                    value="{{ date('H:i', strtotime($editModeData->min_duration)) }}"
                                                    placeholder="@lang('leave.min_duration')" name="min_duration"
                                                    id="min_duration" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="col-md-11">
                                            <div class="form-group bootstrap-timepicker">
                                                <label for="exampleInput">@lang('leave.max_duration') <span
                                                        class="validateRq">*</span></label>
                                                <input class="form-control timePicker required" type="text"
                                                    value="{{ date('H:i', strtotime($editModeData->max_duration)) }}"
                                                    placeholder="@lang('leave.max_duration')" name="max_duration"
                                                    id="max_duration" readonly>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="col-md-11">
                                            <div class="form-group bootstrap-timepicker">
                                                <label for="exampleInput">@lang('leave.total_duration') <span
                                                        class="validateRq">*</span></label>
                                                <input class="form-control timePicker required" type="text"
                                                    value="{{ date('H:i', strtotime($editModeData->total_duration)) }}"
                                                    placeholder="@lang('leave.total_duration')" name="total_duration"
                                                    id="total_duration" readonly>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="col-md-11">
                                            <div class="form-group">
                                                <label for="exampleInput">@lang('leave.monthly_limit') <small> in No of
                                                        Days</small><span class="validateRq">*</span></label>
                                                {!! Form::number(
                                                    'monthly_limit',
                                                    $editModeData->monthly_limit,
                                                    $attributes = [
                                                        'class' => 'form-control monthly_limit required',
                                                        'placeholder' => __('common.monthly_limit'),
                                                    ],
                                                ) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="text-center col-md-12">
                                            <button type="submit" class="btn btn-info btn_style"><i
                                                    class="fa fa-pencil"></i> @lang('common.update')</button>
                                        </div>
                                    </div>
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
    var minuteStep = 1;
    var maxDuration = 1;

    $(document).ready(function() {
        $.ajax({
            type: "get",
            url: "{{ route('permissionMaster.index') }}",
            dataType: "json",
            success: function(response) {
                minuteStep = response.min;
                maxDuration = response.max;
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
            minuteStep: minuteStepFrom,
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
            minuteStep: minuteStepTo,
        }).on('changeTime.timepicker', function(e) {
            // console.log('The time is ' + e.time.value);
            // console.log('The hour is ' + e.time.hours);
            // console.log('The minute is ' + e.time.minutes);
            // console.log('The meridian is ' + e.time.meridian);
        });
    });
</script>
@endsection

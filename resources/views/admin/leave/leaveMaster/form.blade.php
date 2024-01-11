@extends('admin.master')
@section('content')

@section('title')
    @if (isset($editModeData))
        @lang('leave.edit_leave_master');
    @else
        @lang('leave.add_leave_master');
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
        <div class="col-lg-9 col-md-8 col-sm-8 col-xs-12">
            <a href="{{ route('leaveMaster.index') }}"
                class="btn btn-success pull-right m-l-20 hidden-xs hidden-sm waves-effect waves-light"><i
                    class="fa fa-list-ul" aria-hidden="true"></i> @lang('leave.view_leave_master')</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading"><i class="mdi mdi-clipboard-text fa-fw"></i>@yield('title')</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        @if (isset($editModeData))
                            {{ Form::model($editModeData, ['route' => ['leaveMaster.update', $editModeData->leave_master_id], 'method' => 'PUT', 'files' => 'true', 'id' => 'leaveMasterForm', 'class' => 'form-horizontal']) }}
                        @else
                            {{ Form::open(['route' => 'leaveMaster.store', 'enctype' => 'multipart/form-data', 'id' => 'leaveMasterForm', 'class' => 'form-horizontal']) }}
                        @endif

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

                            <div class="row" style="padding:0 15%;">
                                @if (isset($editModeData))
                                    <input type="text" name="leave_master_id"
                                        value="{{ $editModeData->leave_master_id }}" hidden>
                                @endif
                                <div class="col-md-3">
                                    <label class="control-label">@lang('leave.employee_name')<span
                                            class="validateRq">*</span>:</label>
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            {!! Form::select(
                                                'finger_print_id',
                                                employeeList(),
                                                isset($editModeData->finger_print_id) ? $editModeData->finger_print_id : old('finger_print_id'),
                                                [
                                                    'class' => 'form-control required select2 finger_print_id',
                                                    'id' => 'finger_print_id',
                                                ],
                                            ) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">@lang('leave.leave_type_name')<span
                                            class="validateRq">*</span>:</label>
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            {!! Form::select(
                                                'leave_type_id',
                                                $leaveTypeList,
                                                isset($editModeData->leave_type_id) ? $editModeData->leave_type_id : old('leave_type_id'),
                                                [
                                                    'class' => 'form-control required select2 leave_type_id',
                                                ],
                                            ) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">@lang('leave.number_of_day')<span
                                            class="validateRq">*</span>:</label>
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            {!! Form::text(
                                                'num_of_day',
                                                old('num_of_day'),
                                                $attributes = [
                                                    'class' => 'form-control required num_of_day',
                                                    'id' => 'num_of_day',
                                                    'placeholder' => __('leave.number_of_day'),
                                                ],
                                            ) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions col-md-3">
                                    <label class="control-label" style="color: transparent">.</label>
                                    <div class="form-group">
                                        <div class="col-md-12">
                                            @if (isset($editModeData))
                                                <button type="submit" class="btn btn-info btn_style"><i
                                                        class="fa fa-pencil"></i>
                                                    @lang('common.update')</button>
                                            @else
                                                <button type="submit" class="btn btn-info btn_style"><i
                                                        class="fa fa-check"></i> @lang('common.save')</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="row">
                                <div class="table-responsive">
                                    <table id="myDataTable" class="table table-bordered">
                                        <thead class="tr_header">
                                            <tr>
                                                <th>@lang('common.serial')</th>
                                                <th>@lang('leave.leave_type_name')</th>
                                                <th>@lang('leave.employee_name')</th>
                                                <th>@lang('leave.employee_id')</th>
                                                <th>@lang('leave.number_of_day')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {!! $sl = null !!}
                                            @foreach ($leaveMasterLists as $leaveMasterList)
                                                @foreach ($leaveMasterList->leaveMaster as $value)
                                                    <tr class="{!! $value->leave_master_id !!} {!! isset($editModeData->leave_master_id) && $editModeData->leave_type_id == $value->leaveType->leave_type_id
                                                        ? 'bg-info'
                                                        : '' !!}">
                                                        <td style="width: 100px;">{!! ++$sl !!}</td>
                                                        <td>{!! $value->leaveType->leave_type_name !!}</td>
                                                        <td>{!! $leaveMasterList->first_name . ' ' . $leaveMasterList->last_name !!}</td>
                                                        <td>{!! $leaveMasterList->finger_id !!}</td>
                                                        <td>{!! $value->num_of_day !!}</td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div> --}}

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
<script type="text/javascript">
    // aria-controls="myDataTable"

    $(function() {
        $("input[type='search']").val($("#finger_print_id").val());
        $("#finger_print_id").attr('selected', 'selected');
        showTable();
    });


    function showTable() {
        var showTable = $("input[type='search']").val() != null;
        if (showTable) {
            $('.table-responsive').show();
        } else {
            $('.table-responsive').hide();
        }
    }



    $(document).on("change", "#finger_print_id", function() {
        var id = $(this).val();
        $('#finger_print_id option').each(function(index, element) {
            if (id == element.value) {
                $("input[type='search']").val(element.text);
                $("input[type='search']").trigger({
                    type: 'keyup'
                });
                // $("#myDataTable_filter").html(
                //     '<p style="font-weight:500;padding:0 12px"> ' + element.text + ' </p>');
                showTable();
            }
        });

    })
</script>
@endsection

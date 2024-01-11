@extends('admin.master')
@section('content')
@section('title')
    @lang('leave.leave_master_list')
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
        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
            <a href="{{ route('leaveMaster.create') }}"
                class="btn btn-success pull-right m-l-20 hidden-xs hidden-sm waves-effect waves-light"> <i
                    class="fa fa-plus-circle" aria-hidden="true"></i> @lang('leave.add_leave_master')</a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-info">
                <div class="panel-heading"><i class="mdi mdi-table fa-fw"></i> @yield('title')</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
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
                                <i
                                    class="glyphicon glyphicon-remove"></i>&nbsp;<strong>{{ session()->get('error') }}</strong>
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger alert-block alert-dismissable">
                                <ul>
                                    <button type="button" class="close" data-dismiss="alert">x</button>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="border"
                            style="border: 1px solid #EFEEEF;border-radius:4px;margin-bottom:12px;padding:12px">
                            <a class="pull-right" href="{{ route('templates.leaveMasterTemplate') }}">
                                <div class="btn btn-success btn-sm" value="Template" type="submit">
                                    <i class="fa fa-download" aria-hidden="true"></i><span>
                                        Download</span>
                                </div>
                            </a>
                            <div class="row hidden-xs hidden-sm">
                                <p class="border" style="margin-left:18px">
                                    <span><i class="fa fa-upload"></i></span>
                                    <span style="margin-left: 4px"> Import Leave Master excel file.</span>
                                </p>
                                <form action="{{ url('leaveMaster/import') }}" method="post"
                                    enctype="multipart/form-data">
                                    {{ csrf_field() }}
                                    <div class="row">
                                        <div>
                                            <div class="col-md-4 text-right" style="margin-left:14px">
                                                <input type="file" name="select_file"
                                                    class="form-control custom-file-upload">
                                            </div>
                                            <div class="col-sm-1">
                                                <button class="btn btn-success btn-sm" type="submit"><span><i
                                                            class="fa fa-upload" aria-hidden="true"></i></span>
                                                    Upload</button>
                                            </div>
                                        </div>

                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="myDataTable" class="table table-bordered">
                                <thead class="tr_header">
                                    <tr>
                                        <th>@lang('common.serial')</th>
                                        <th>@lang('leave.leave_type_name')</th>
                                        <th>@lang('leave.employee_id')</th>
                                        <th>@lang('leave.employee_name')</th>
                                        <th>@lang('employee.branch')</th>
                                        <th>@lang('employee.department')</th>
                                        <th>@lang('leave.number_of_day')</th>
                                        <th style="text-align: center;">@lang('common.action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {!! $sl = null !!}
                                    @foreach ($results as $leaveMasterList)
                                        @foreach ($leaveMasterList->leaveMaster as $value)
                                            <tr class="{!! $value->leave_master_id !!}">
                                                <td style="width: 100px;">{!! ++$sl !!}</td>
                                                <td>{!! $value->leaveType->leave_type_name !!}</td>
                                                <td>{!! $leaveMasterList->finger_id !!}</td>
                                                <td>{!! $leaveMasterList->first_name . ' ' . $leaveMasterList->last_name !!}</td>
                                                <td>{!! $leaveMasterList->branch->branch_name !!}</td>
                                                <td>{!! $leaveMasterList->department->department_name !!}</td>
                                                <td>{!! $value->num_of_day !!}</td>
                                                <td style="width: 100px;">
                                                    <a href="{!! route('leaveMaster.edit', $value->leave_master_id) !!}"
                                                        class="btn btn-success btn-xs btnColor">
                                                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="{!! route('leaveMaster.delete', $value->leave_master_id) !!}"
                                                        data-token="{!! csrf_token() !!}"
                                                        data-id="{!! $value->leave_master_id !!}"
                                                        class="btnColor delete btn btn-danger btn-xs deleteBtn"><i
                                                            class="fa fa-trash-o" aria-hidden="true"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
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

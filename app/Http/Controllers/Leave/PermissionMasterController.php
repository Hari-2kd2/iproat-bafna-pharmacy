<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionMasterRequest;
use App\Model\PermissionMaster;
use Illuminate\Http\Request;

class PermissionMasterController extends Controller
{

    public function index(Request $request)
    {
        $results = PermissionMaster::get();

        if ($request->ajax()) {
            $setting = PermissionMaster::first();
            $min = minutes($setting->min_duration);
            $max = minutes($setting->max_duration);
            $total = minutes($setting->total_duration);
            return response()->json(['min' => $min, 'total_hours' => date('H', strtotime($setting->total_duration)), 'total_minutes' => date('i', strtotime($setting->total_duration)), 'max_hours' => date('H', strtotime($setting->max_duration)), 'max_minutes' => date('i', strtotime($setting->max_duration)), 'monthly_limit' => $setting->monthly_limit]);
        }

        return view('admin.leave.permissionMaster.index', ['results' => $results]);
    }

    public function create()
    {
        //
    }

    public function store(PermissionMasterRequest $request)
    {
        //
    }

    public function edit()
    {
        $editModeData = PermissionMaster::first();
        return view('admin.leave.permissionMaster.form', ['editModeData' => $editModeData]);
    }

    public function update(PermissionMasterRequest $request, $id)
    {
        $data = PermissionMaster::findOrFail($id);
        $input = $request->all();
        try {
            $data->update($input);
            $bug = 0;
        } catch (\Exception $e) {
            $bug = 1;
        }

        if ($bug == 0) {
            return redirect('permissionMaster')->with('success', 'Permission Master successfully updated.');
        } else {
            return redirect('permissionMaster')->with('error', 'Something Error Found !, Please try again.');
        }
    }

    public function destroy($id)
    {
        //
    }
}

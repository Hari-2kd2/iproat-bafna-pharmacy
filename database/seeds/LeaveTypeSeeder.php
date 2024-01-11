<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $time = Carbon::now();
        DB::table('leave_type')->truncate();
        DB::table('leave_type')->insert(
            [
                ['leave_type_name' => 'Earn Leave',  'created_at' => $time, 'updated_at' => $time, 'branch_id' => 1],
                ['leave_type_name' => 'Paid Leave', 'created_at' => $time, 'updated_at' => $time, 'branch_id' => 1],
                ['leave_type_name' => 'Casual Leave',  'created_at' => $time, 'updated_at' => $time, 'branch_id' => 1],
                ['leave_type_name' => 'Sick Leave',  'created_at' => $time, 'updated_at' => $time, 'branch_id' => 1],
            ]
        );
    }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_master_id', 'leave_type_id', 'finger_print_id',  'num_of_day'
    ];

    protected $primaryKey = 'leave_master_id';

    public function leaveType()
    {
        return $this->hasOne(LeaveType::class, 'leave_type_id', 'leave_type_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'finger_id', 'finger_print_id');
    }
}

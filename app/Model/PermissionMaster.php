<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionMaster extends Model
{
    use HasFactory;

    protected $primaryKey = 'permission_master_id';

    protected $fillable = [
        'permission_master_id', 'min_duration', 'max_duration', 'total_duration', 'monthly_limit', 'created_by', 'updated_by',
    ];
}

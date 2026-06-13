<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerEnvironmentLog extends Model
{
    protected $fillable = [
        'user_id',
        'pmv',
        'ppd',
        'clothing_insulation',
        'activity_name',
        'activity_met',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvironmentLog extends Model
{
    use HasFactory;

    // Tambahkan baris di bawah ini:
    protected $fillable = [
        'wbgt',
        'unit_id',
        'air_speed',
        'twb',
        'tdb',
        'mrt',
        'rh',
        'o2',
        'co'
    ];
}

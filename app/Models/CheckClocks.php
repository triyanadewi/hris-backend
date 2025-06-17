<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CheckClockSettings;

class CheckClocks extends Model
{
    use SoftDeletes;

    protected $table = 'check_clocks';

    protected $fillable = [
        'id',
        'employee_id',
        'ck_settings_id',
        'check_clock_type',
        'check_clock_date',
        'check_clock_time',
        'check_out_time',
        'start_date',
        'end_date',
        'status',
        'approved',
        'location',
        'address',
        'latitude',
        'longitude',
        'photo',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'check_clock_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'check_clock_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
    ];

    // Relasi ke Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Relasi ke CheckClockSetting
    public function checkClockSettings()
    {
        return $this->belongsTo(CheckClockSettings::class, 'ck_settings_id');
    }
}

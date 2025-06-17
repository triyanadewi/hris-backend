<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockSettingTimes extends Model
{
    use SoftDeletes;

    protected $table = 'check_clock_setting_times';

    protected $fillable = [
        'ck_settings_id',
        'day',
        'clock_in_start',
        'clock_in_end',
        'clock_in_on_time_limit',
        'clock_out_start',
        'clock_out_end',
        'work_day',
    ];

    // protected $casts = [
    //     'work_day' => 'boolean',
    //     'clock_in' => 'datetime:H:i:s',
    //     'clock_out' => 'datetime:H:i:s',
    //     'break_start' => 'datetime:H:i:s',
    //     'break_end' => 'datetime:H:i:s',
    // ];

    // Relasi ke CheckClockSetting
    public function setting()
    {
        return $this->belongsTo(CheckClockSettings::class, 'ck_settings_id');
    }
    
}

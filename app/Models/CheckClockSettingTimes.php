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

    protected $casts = [
        'work_day' => 'boolean',
    ];

    // Accessor methods for time fields to ensure they return time strings
    public function getClockInStartAttribute($value)
    {
        return $value;
    }

    public function getClockInEndAttribute($value)
    {
        return $value;
    }

    public function getClockInOnTimeLimitAttribute($value)
    {
        return $value;
    }

    public function getClockOutStartAttribute($value)
    {
        return $value;
    }

    public function getClockOutEndAttribute($value)
    {
        return $value;
    }

    // Relasi ke CheckClockSetting
    public function setting()
    {
        return $this->belongsTo(CheckClockSettings::class, 'ck_settings_id');
    }

    // Scope untuk mendapatkan hari kerja
    public function scopeWorkDays($query)
    {
        return $query->where('work_day', true);
    }

    // Scope untuk mendapatkan hari libur
    public function scopeNonWorkDays($query)
    {
        return $query->where('work_day', false);
    }

    // Scope untuk mendapatkan setting hari tertentu
    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    // Method untuk mengecek apakah waktu clock-in masih dalam batas
    public function isClockInTime($time)
    {
        $clockInStart = strtotime($this->clock_in_start);
        $clockInEnd = strtotime($this->clock_in_end);
        $checkTime = strtotime($time);

        return $checkTime >= $clockInStart && $checkTime <= $clockInEnd;
    }

    // Method untuk mengecek apakah clock-in terlambat
    public function isLate($time)
    {
        $onTimeLimit = strtotime($this->clock_in_on_time_limit);
        $checkTime = strtotime($time);

        return $checkTime > $onTimeLimit;
    }

    // Method untuk mengecek apakah waktu clock-out sudah bisa dilakukan
    public function canClockOut($time)
    {
        $clockOutStart = strtotime($this->clock_out_start);
        $checkTime = strtotime($time);

        return $checkTime >= $clockOutStart;
    }
}

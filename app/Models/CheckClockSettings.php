<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockSettings extends Model
{
    use SoftDeletes;

    protected $table = 'check_clock_settings';

    protected $fillable = [
        // 'name',
        // 'type',
        'latitude',
        'longitude',
        'radius',
    ];

    // Relasi ke waktu check clock
    public function times()
    {
        return $this->hasMany(CheckClockSettingTimes::class, 'ck_settings_id');
    }

    // Relasi ke presensi check clock
    public function checkClocks()
    {
        return $this->hasMany(CheckClocks::class, 'ck_settings_id');
    }
}

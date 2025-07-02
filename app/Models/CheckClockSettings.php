<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockSettings extends Model
{
    use SoftDeletes;

    protected $table = 'check_clock_settings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'location_name',
        'latitude',
        'longitude',
        'radius',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'integer',
    ];

    // Relasi ke Company
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Relasi ke Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

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

    // Scope untuk mendapatkan setting berdasarkan company
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Scope untuk mendapatkan setting berdasarkan branch
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Accessor untuk nama lokasi yang lebih descriptive
    public function getFullLocationNameAttribute()
    {
        if ($this->branch) {
            return $this->location_name . ' - ' . $this->branch->name;
        }
        return $this->location_name . ' - Head Office';
    }
}

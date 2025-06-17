<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'max_branches',
        'max_employees',
        'access_duration_days',
        'is_active'
    ];

    protected $casts = [
        'max_branches' => 'integer',
        'max_employees' => 'integer',
        'access_duration_days' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
     public function orders()
    {
        return $this->hasMany(Order::class, 'package_benefit_id'); // UPDATED
    }

    // Scope methods
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPackage($query, $packageId)
    {
        return $query->where('package_id', $packageId);
    }
}
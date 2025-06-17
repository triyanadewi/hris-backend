<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'package_id',
        'extra_branch',
        'extra_employee',
        'starts_at',
        'ends_at',
        'is_active'
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'extra_branch' => 'integer',
        'extra_employee' => 'integer'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active && $this->ends_at >= now()->toDateString();
    }

    public function isExpired()
    {
        return $this->ends_at < now()->toDateString();
    }

    public function daysRemaining()
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return Carbon::parse($this->ends_at)->diffInDays(now());
    }

    // Scope methods
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('ends_at', '>=', now()->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now()->toDateString());
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
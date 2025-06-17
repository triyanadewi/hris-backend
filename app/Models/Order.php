<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_benefits_id',
        'company_id',
        'company_name',      // Add this
        'email',             // Add this
        'phone_number',      // Add this
        'add_branch',
        'add_employees',
        'duration_days',
        'subtotal',
        'tax',
        'total',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function packageBenefit()
    {
        return $this->belongsTo(PackageBenefit::class, 'package_benefits_id');
    }
}
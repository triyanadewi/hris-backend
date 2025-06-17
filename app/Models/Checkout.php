<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    protected $fillable = [
        'plan',
        'company_id',
        'branches',
        'addon_employees',
        'subtotal',
        'tax',
        'total',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

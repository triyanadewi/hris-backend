<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'head_office_phone',
        'head_office_phone_backup',
        'head_office_address',
        'description',
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

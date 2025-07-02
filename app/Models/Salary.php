<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salary extends Model
{
    use SoftDeletes;

    protected $table = 'salaries';

    protected $fillable = [
        'user_id',
        'type',
        'rate',
        'effective_date',
        'status',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'rate' => 'float',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Letters extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'letter_format_id',
        'user_id',
        'resignation_date',
        'reason_resign',
        'additional_notes',
        'current_division',
        'requested_division',
        'reason_transfer',
        'current_salary',
        'requested_salary',
        'reason_salary',
        'leave_start',
        'return_to_work',
        'reason_for_leave',
        'is_sent',
        'is_approval',
    ];

    public function letterFormat()
    {
        return $this->belongsTo(Letters::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}

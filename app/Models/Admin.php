<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{

    protected $table = 'admin';

    protected $fillable = [
        'user_id', 
        'phone_number', 
        'profile_photo'
    ];

    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

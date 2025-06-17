<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letter_formats extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'content',
        'status',
    ];
}

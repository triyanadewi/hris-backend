<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'employee_id',
        'file_path',
        'original_filename',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected $appends = ['url', 'original_filename'];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function getOriginalFilenameAttribute()
    {
        return $this->attributes['original_filename'] ?? basename($this->file_path);
    }
}

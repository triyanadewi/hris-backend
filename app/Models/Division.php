<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
    ];


    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        // Assuming 'Division_id' is the foreign key in the 'employees' table
        return $this->hasMany(Employee::class, 'Division_id');
    }
}


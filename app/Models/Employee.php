<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\EmployeeAchievement;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        // ... other fillable fields from your migration
        'user_id',
        'Company_id',
        'EmployeeID',
        'FirstName',
        'LastName',
        'Gender',
        'Address',
        'PhoneNumber',
        'Company_id',
        'Branch_id',
        'Division_id',
        'Position_id',
        'Status',
        'NIK',
        'LastEducation',
        'PlaceOfBirth',
        'BirthDate',
        'ContractType',
        'Bank',
        'BankAccountNumber',
        'BankAccountHolderName',
        'photo',
        'Notes',
        'EmployeeID', // Ensure this is fillable
    ];

    /**
     * Get the user that owns the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Add relationships to Branch, Division, Position if needed
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'Branch_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'Division_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'Position_id');
    }
}
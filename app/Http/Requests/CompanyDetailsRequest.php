<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Branch validation
            'branches' => 'required|array|min:1',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.branch_address' => 'required|string|max:500',
            'branches.*.branch_phone' => 'required|string|max:20|regex:/^[0-9\-\+\(\)\s]+$/',
            'branches.*.branch_phone_backup' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]+$/',
            'branches.*.description' => 'nullable|string|max:1000',

            // Division validation
            'divisions' => 'required|array|min:1',
            'divisions.*.branch_id' => 'required|integer|min:0',
            'divisions.*.name' => 'required|string|max:255',
            'divisions.*.description' => 'nullable|string|max:1000',

            // Position validation
            'positions' => 'required|array|min:1',
            'positions.*.division_id' => 'required|integer|min:0',
            'positions.*.name' => 'required|string|max:255',
            'positions.*.description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branches.required' => 'At least one branch is required.',
            'branches.*.name.required' => 'Branch name is required.',
            'branches.*.branch_address.required' => 'Branch address is required.',
            'branches.*.branch_phone.required' => 'Branch phone is required.',
            'branches.*.branch_phone.regex' => 'Branch phone format is invalid.',
            'branches.*.branch_phone_backup.regex' => 'Backup phone format is invalid.',

            'divisions.required' => 'At least one division is required.',
            'divisions.*.branch_id.required' => 'Division must be assigned to a branch.',
            'divisions.*.name.required' => 'Division name is required.',

            'positions.required' => 'At least one position is required.',
            'positions.*.division_id.required' => 'Position must be assigned to a division.',
            'positions.*.name.required' => 'Position name is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'branches.*.name' => 'branch name',
            'branches.*.branch_address' => 'branch address',
            'branches.*.branch_phone' => 'branch phone',
            'branches.*.branch_phone_backup' => 'backup phone',
            'branches.*.description' => 'branch description',
            
            'divisions.*.branch_id' => 'branch assignment',
            'divisions.*.name' => 'division name',
            'divisions.*.description' => 'division description',
            
            'positions.*.division_id' => 'division assignment',
            'positions.*.name' => 'position name',
            'positions.*.description' => 'position description',
        ];
    }
}
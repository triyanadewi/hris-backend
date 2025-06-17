<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Achievement;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\Company;

class EmployeeController extends Controller
{
    /**
     * Check if company has active subscription and employee limit
     */
    private function checkSubscriptionLimits($companyId, $excludeEmployeeId = null)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return ['status' => false, 'message' => 'Company not found'];
        }

        // Get active subscription
        $subscription = Subscription::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('package.packageBenefits')
            ->first();

        if (!$subscription) {
            return ['status' => false, 'message' => 'No active subscription found'];
        }

        // Get current employee count (excluding the employee being updated if any)
        $currentEmployeeCount = Employee::where('Company_id', $companyId);
        if ($excludeEmployeeId) {
            $currentEmployeeCount->where('id', '!=', $excludeEmployeeId);
        }
        $currentEmployeeCount = $currentEmployeeCount->count();

        // Calculate total employee limit
        $packageBenefit = $subscription->package->packageBenefits->first();
        $totalEmployeeLimit = $packageBenefit->max_employees + $subscription->extra_employee;

        if ($currentEmployeeCount >= $totalEmployeeLimit) {
            return [
                'status' => false, 
                'message' => "Employee limit reached. Current: {$currentEmployeeCount}, Limit: {$totalEmployeeLimit}"
            ];
        }

        return [
            'status' => true, 
            'current_count' => $currentEmployeeCount,
            'limit' => $totalEmployeeLimit,
            'subscription' => $subscription
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get company ID from authenticated user or request
        $companyId = $request->input('company_id', 1); // Default to 1 for testing
        // $companyId = auth()->user()->company_id; // Use this when authentication is implemented

        // Check subscription status
        $subscriptionCheck = $this->checkSubscriptionLimits($companyId);
        
        $query = Employee::with('achievements')
            ->where('Company_id', $companyId); // Filter by company

        if ($request->has('status')) {
            $query->whereIn('status', $request->input('status'));
        }

        if ($request->has('division')) {
            $query->whereIn('Division_id', $request->input('division'));
        }

        if ($request->has('position')) {
            $query->whereIn('Position_id', $request->input('position'));
        }

        if ($request->has('branch')) {
            $query->whereIn('Branch_id', $request->input('branch'));
        }

        $employees = $query->get()->map(function ($emp) {
            return [
                ...$emp->toArray(),
                'photo_url' => $emp->photo ? asset('storage/' . $emp->photo) : null,
                'Achievements' => $emp->achievements,
                'achievement_files' => $emp->achievements->map(function ($ach) {
                    return [
                        'name' => $ach->original_filename ?? basename($ach->file_path),
                        'url' => asset('storage/' . $ach->file_path),
                    ];
                }),
                'Branch' => $emp->branch?->name,
                'Division' => $emp->division?->name,
                'Position' => $emp->position?->name,
            ];
        });

        return response()->json([
            'employees' => $employees,
            'subscription_info' => [
                'has_active_subscription' => $subscriptionCheck['status'],
                'current_employee_count' => $subscriptionCheck['current_count'] ?? 0,
                'employee_limit' => $subscriptionCheck['limit'] ?? 0,
                'message' => $subscriptionCheck['message'] ?? null
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'FirstName' => 'required|string',
            'LastName' => 'required|string',
            'Gender' => 'required|string|in:Male,Female',
            'Address' => 'required|string',
            'PhoneNumber' => 'required|string',
            'Branch_id' => 'required|integer|exists:branches,id',
            'Division_id' => 'required|integer|exists:divisions,id',
            'Position_id' => 'required|integer|exists:positions,id',
            'Status' => 'required|string|in:Aktif,Non Aktif',
            'NIK' => 'required|string',
            'LastEducation' => 'required|string',
            'PlaceOfBirth' => 'required|string',
            'BirthDate' => 'required|string',
            'ContractType' => 'required|string',
            'Bank' => 'required|string',
            'BankAccountNumber' => 'required|string',
            'BankAccountHolderName' => 'required|string',
            'photo' => 'required|file|image|mimes:jpg,jpeg,png|max:2048',
            'Notes' => 'nullable|string',
            'Achievements' => 'nullable|array',
            'Achievements.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $validated['user_id'] = 1;
        $validated['Company_id'] = 1;
        // $validated['user_id'] = auth()->id();
        // $validated['Company_id'] = auth()->user()->company_id;

        // Check subscription limits before creating employee
        $subscriptionCheck = $this->checkSubscriptionLimits($validated['Company_id']);
        if (!$subscriptionCheck['status']) {
            return response()->json([
                'message' => $subscriptionCheck['message'],
                'subscription_required' => true
            ], 403);
        }

        $branch = Branch::find($validated['Branch_id']);
        $division = Division::find($validated['Division_id']);
        $position = Position::find($validated['Position_id']);

        // Validasi hubungan antar entitas
        if (!$branch || $branch->company_id != $validated['Company_id']) {
            return response()->json(['message' => 'Branch tidak sesuai dengan Company'], 422);
        }

        if (!$division || $division->branch_id != $validated['Branch_id']) {
            return response()->json(['message' => 'Division tidak sesuai dengan Branch'], 422);
        }

        if (!$position || $position->division_id != $validated['Division_id']) {
            return response()->json(['message' => 'Position tidak sesuai dengan Division'], 422);
        }

        $prefix = str_pad($validated['Company_id'], 2, '0', STR_PAD_LEFT)
            . str_pad($validated['Branch_id'], 2, '0', STR_PAD_LEFT)
            . str_pad($validated['Division_id'], 2, '0', STR_PAD_LEFT)
            . str_pad($validated['Position_id'], 2, '0', STR_PAD_LEFT);

        $count = Employee::where('EmployeeID', 'like', $prefix . '%')->count();
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $validated['EmployeeID'] = $prefix . $sequence;

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
            $photoPath = $photo->storeAs('employee_photos', $filename, 'public');
            $validated['photo'] = $photoPath;
        }

        $employee = Employee::create($validated);

        $achievementFiles = $request->file('Achievements', []);
        foreach ($achievementFiles as $achievement) {
            if (isset($achievement['file']) && $achievement['file']) {
                $file = $achievement['file'];
                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $folder = "employee_achievements/{$employee->id}";
                $filePath = $file->storeAs($folder, $fileName, 'public');

                Achievement::create([
                    'employee_id' => $employee->id,
                    'file_path' => $filePath,
                    'original_filename' => $file->getClientOriginalName(),
                ]);
            }
        }

        $employee->photo_url = $employee->photo ? asset('storage/' . $employee->photo) : null;

        return response()->json([
            'employee' => $employee,
            'subscription_info' => [
                'current_employee_count' => $subscriptionCheck['current_count'] + 1,
                'employee_limit' => $subscriptionCheck['limit']
            ]
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'FirstName' => 'required|string',
            'LastName' => 'required|string',
            'Gender' => 'required|string|in:Male,Female',
            'Address' => 'required|string',
            'PhoneNumber' => 'required|string',
            'Branch_id' => 'required|integer|exists:branches,id',
            'Division_id' => 'required|integer|exists:divisions,id',
            'Position_id' => 'required|integer|exists:positions,id',
            'Status' => 'required|string|in:Aktif,Non Aktif',
            'NIK' => 'required|string',
            'LastEducation' => 'required|string',
            'PlaceOfBirth' => 'required|string',
            'BirthDate' => 'required|string',
            'ContractType' => 'required|string',
            'Bank' => 'required|string',
            'BankAccountNumber' => 'required|string',
            'BankAccountHolderName' => 'required|string',
            'photo' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048',
            'Notes' => 'nullable|string',
            'Achievements' => 'nullable|array',
            'Achievements.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ]);

        $validated['user_id'] = 1;
        $validated['Company_id'] = 1;
        // $validated['user_id'] = auth()->id();
        // $validated['Company_id'] = auth()->user()->company_id;

        // Check subscription status (excluding current employee from count)
        $subscriptionCheck = $this->checkSubscriptionLimits($validated['Company_id'], $employee->id);
        if (!$subscriptionCheck['status']) {
            return response()->json([
                'message' => $subscriptionCheck['message'],
                'subscription_required' => true
            ], 403);
        }

        $branch = Branch::find($validated['Branch_id']);
        $division = Division::find($validated['Division_id']);
        $position = Position::find($validated['Position_id']);

        // Validasi hubungan antar entitas
        if (!$branch || $branch->company_id != $validated['Company_id']) {
            return response()->json(['message' => 'Branch tidak sesuai dengan Company'], 422);
        }

        if (!$division || $division->branch_id != $validated['Branch_id']) {
            return response()->json(['message' => 'Division tidak sesuai dengan Branch'], 422);
        }

        if (!$position || $position->division_id != $validated['Division_id']) {
            return response()->json(['message' => 'Position tidak sesuai dengan Division'], 422);
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }

            $photo = $request->file('photo');
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
            $photoPath = $photo->storeAs('employee_photos', $filename, 'public');
            $validated['photo'] = $photoPath;
        } else {
            unset($validated['photo']);
        }

        $employee->update($validated);

        $achievementFiles = $request->file('Achievements', []);
        foreach ($achievementFiles as $achievement) {
            if (isset($achievement['file']) && $achievement['file']) {
                $file = $achievement['file'];
                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $folder = "employee_achievements/{$employee->id}";
                $filePath = $file->storeAs($folder, $fileName, 'public');

                Achievement::create([
                    'employee_id' => $employee->id,
                    'file_path' => $filePath,
                    'original_filename' => $file->getClientOriginalName(),
                ]);
            }
        }

        $employee->load('achievements');

        $employee->photo_url = $employee->photo ? asset('storage/' . $employee->photo) : null;
        $employee->achievement_files = $employee->achievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'name' => $achievement->original_filename ?? basename($achievement->file_path),
                'url' => asset('storage/' . $achievement->file_path),
                'original_filename' => $achievement->original_filename,
            ];
        });

        return response()->json($employee);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $employee = Employee::with('achievements')->findOrFail($id);

            // Delete employee photo
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }

            // Delete achievement files
            foreach ($employee->achievements as $achievement) {
                if ($achievement->file_path && Storage::disk('public')->exists($achievement->file_path)) {
                    Storage::disk('public')->delete($achievement->file_path);
                }
            }

            // Delete achievement folder if empty
            $achievementFolder = "employee_achievements/{$employee->id}";
            if (Storage::disk('public')->exists($achievementFolder)) {
                Storage::disk('public')->deleteDirectory($achievementFolder);
            }

            $employee->delete();

            return response()->json([
                'message' => 'Data pegawai berhasil dihapus',
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete employee.',
                'success' => false
            ], 500);
        }
    }

    public function show($id)
    {
        $employee = Employee::with(['achievements', 'branch', 'division', 'position'])->findOrFail($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->photo_url = $employee->photo ? asset('storage/' . $employee->photo) : null;

        $employee->achievement_files = $employee->achievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'name' => $achievement->original_filename ?? basename($achievement->file_path),
                'url' => asset('storage/' . $achievement->file_path),
                'original_filename' => $achievement->original_filename,
            ];
        });
        unset($employee->achievements);

        return response()->json($employee);
    }

    public function removeAchievement($id)
    {
        try {
            $achievement = Achievement::findOrFail($id);

            // Delete file from storage
            if ($achievement->file_path && Storage::disk('public')->exists($achievement->file_path)) {
                Storage::disk('public')->delete($achievement->file_path);
            }

            // Delete database record
            $achievement->delete();

            return response()->json([
                'message' => 'Achievement file deleted successfully.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting achievement: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete achievement file.',
                'success' => false
            ], 500);
        }
    }

    /**
     * Get subscription status and limits for current company
     */
    public function getSubscriptionStatus(Request $request)
    {
        $companyId = $request->input('company_id', 1);
        // $companyId = auth()->user()->company_id;

        $subscriptionCheck = $this->checkSubscriptionLimits($companyId);

        return response()->json([
            'has_active_subscription' => $subscriptionCheck['status'],
            'current_employee_count' => $subscriptionCheck['current_count'] ?? 0,
            'employee_limit' => $subscriptionCheck['limit'] ?? 0,
            'remaining_slots' => $subscriptionCheck['status'] ? 
                ($subscriptionCheck['limit'] - $subscriptionCheck['current_count']) : 0,
            'message' => $subscriptionCheck['message'] ?? null,
            'subscription' => $subscriptionCheck['subscription'] ?? null
        ]);
    }
}
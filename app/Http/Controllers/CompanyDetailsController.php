<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\PackageBenefit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyDetailsController extends Controller
{
    private function getCompanyId()
    {
        // Hardcode sementara untuk development (sama seperti CompanyController)
        $user = User::find(1);
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        if (!$user->company_id) {
            throw new \Exception('User has no company. Please create company profile first.');
        }
        
        return $user->company_id;
    }

    public function store(Request $request)
    {
        try {
            $companyId = $this->getCompanyId();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
        
        // Debug: Log request data
        \Log::info('Request data:', $request->all());
        
        // Validasi dasar
        $request->validate([
            'branches' => 'required|array|min:1',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.branch_address' => 'required|string',
            'branches.*.branch_phone' => 'required|string',
            'branches.*.branch_phone_backup' => 'nullable|string',
            'branches.*.description' => 'nullable|string',
            'branches.*.divisions' => 'required|array|min:1',
            'branches.*.divisions.*.name' => 'required|string|max:255',
            'branches.*.divisions.*.description' => 'nullable|string',
            'branches.*.divisions.*.positions' => 'required|array|min:1',
            'branches.*.divisions.*.positions.*.name' => 'required|string|max:255',
            'branches.*.divisions.*.positions.*.description' => 'nullable|string',
        ]);

        // Cek subscription aktif dan limit branch
        $this->validateBranchLimit($companyId, count($request->branches));

        try {
            DB::transaction(function() use ($request, $companyId) {
                foreach ($request->branches as $branchData) {
                    // Simpan branch
                    $branch = Branch::create([
                        'company_id' => $companyId,
                        'name' => $branchData['name'],
                        'branch_address' => $branchData['branch_address'],
                        'branch_phone' => $branchData['branch_phone'],
                        'branch_phone_backup' => $branchData['branch_phone_backup'] ?? null,
                        'description' => $branchData['description'] ?? null,
                    ]);

                    // Simpan divisions untuk branch ini
                    foreach ($branchData['divisions'] as $divisionData) {
                        $division = Division::create([
                            'branch_id' => $branch->id,
                            'name' => $divisionData['name'],
                            'description' => $divisionData['description'] ?? null,
                        ]);

                        // Simpan positions untuk division ini
                        foreach ($divisionData['positions'] as $positionData) {
                            Position::create([
                                'division_id' => $division->id,
                                'name' => $positionData['name'],
                                'description' => $positionData['description'] ?? null,
                            ]);
                        }
                    }
                }
            });

            return response()->json([
                'message' => 'Company details saved successfully',
                'data' => $this->getBranchesData($companyId)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save company details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSubscriptionInfo()
    {
        try {
            $companyId = $this->getCompanyId();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
        
        $subscription = Subscription::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['package.packageBenefit' => function($query) {
                $query->where('is_active', true);
            }])
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found.',
                'data' => null
            ], 404);
        }

        $packageBenefit = $subscription->package->packageBenefit;
        
        if (!$packageBenefit) {
            return response()->json([
                'message' => 'Package benefit not found.',
                'data' => null
            ], 404);
        }

        $currentBranchCount = Branch::where('company_id', $companyId)->count();

        return response()->json([
            'data' => [
                'subscription' => $subscription,
                'package_benefit' => $packageBenefit,
                'max_branches' => $packageBenefit->max_branches + $subscription->extra_branch,
                'max_employees' => $packageBenefit->max_employees + $subscription->extra_employee,
                'current_branch_count' => $currentBranchCount,
                'remaining_branches' => ($packageBenefit->max_branches + $subscription->extra_branch) - $currentBranchCount,
                'subscription_ends_at' => $subscription->ends_at,
                'is_active' => $subscription->is_active
            ]
        ]);
    }

    public function getBranches()
    {
        try {
            $companyId = $this->getCompanyId();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
        
        return response()->json([
            'data' => $this->getBranchesData($companyId)
        ]);
    }

    /**
     * Helper method untuk mengambil data branches
     */
    private function getBranchesData($companyId)
    {
        return Branch::where('company_id', $companyId)
            ->with(['divisions.positions'])
            ->get();
    }

    /**
     * Validasi limit branch berdasarkan subscription aktif
     */
    private function validateBranchLimit($companyId, $requestedBranchCount)
    {
        // Ambil subscription aktif
        $subscription = Subscription::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['package.packageBenefit' => function($query) {
                $query->where('is_active', true);
            }])
            ->first();

        if (!$subscription) {
            throw ValidationException::withMessages([
                'subscription' => 'No active subscription found. Please purchase a package first.'
            ]);
        }

        // Ambil package benefit
        $packageBenefit = $subscription->package->packageBenefit;
        
        if (!$packageBenefit) {
            throw ValidationException::withMessages([
                'package' => 'Package benefits not found.'
            ]);
        }

        // Hitung branch yang sudah ada
        $currentBranchCount = Branch::where('company_id', $companyId)->count();
        
        // Hitung total branch yang akan ada (existing + new)
        $totalBranchCount = $currentBranchCount + $requestedBranchCount;
        
        // Hitung total branch yang diizinkan
        $maxBranches = $packageBenefit->max_branches + $subscription->extra_branch;
        
        // Validasi jumlah branch
        if ($totalBranchCount > $maxBranches) {
            throw ValidationException::withMessages([
                'branches' => "You can only have maximum {$maxBranches} branches. " . 
                             "You currently have {$currentBranchCount} branches. " .
                             "Your current package allows {$packageBenefit->max_branches} branches" . 
                             ($subscription->extra_branch > 0 ? " + {$subscription->extra_branch} additional branches." : ".")
            ]);
        }
    }
}
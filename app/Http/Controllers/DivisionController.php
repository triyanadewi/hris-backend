<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Branch;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

class DivisionController extends Controller
{
    /**
     * Display a listing of divisions filtered by company
     */
    public function index(Request $request)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $divisions = Division::whereHas('branch', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['branch' => function($query) {
                $query->select('id', 'name', 'company_id');
            }])
            ->select('id', 'name', 'branch_id', 'description')
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $divisions
        ]);
    }

    /**
     * Display the specified division
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $division = Division::whereHas('branch', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['branch'])
            ->where('id', $id)
            ->first();

        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found or does not belong to your company'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $division
        ]);
    }

    /**
     * Get divisions by branch (already implemented in EmployeeController)
     */
    public function getByBranch(Request $request, $branchId)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        
        // Validasi bahwa branch belongs to company
        $branch = Branch::where('id', $branchId)
            ->where('company_id', $companyId)
            ->first();
            
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your company'
            ], 404);
        }
        
        $divisions = Division::where('branch_id', $branchId)
            ->select('id', 'name', 'branch_id')
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $divisions
        ]);
    }

    /**
     * Store a newly created division in storage
     */
    public function store(Request $request)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'description' => 'nullable|string',
        ]);

        // Validasi bahwa branch belongs to company
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('company_id', $companyId)
            ->first();
            
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your company'
            ], 404);
        }

        $division = Division::create($validated);

        return response()->json([
            'success' => true,
            'data' => $division,
            'message' => 'Division created successfully'
        ], 201);
    }

    /**
     * Update the specified division in storage
     */
    public function update(Request $request, $id)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $division = Division::whereHas('branch', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('id', $id)
            ->first();

        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found or does not belong to your company'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'description' => 'nullable|string',
        ]);

        // Validasi bahwa branch belongs to company
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('company_id', $companyId)
            ->first();
            
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your company'
            ], 404);
        }

        $division->update($validated);

        return response()->json([
            'success' => true,
            'data' => $division,
            'message' => 'Division updated successfully'
        ]);
    }

    /**
     * Remove the specified division from storage
     */
    public function destroy(Request $request, $id)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $division = Division::whereHas('branch', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('id', $id)
            ->first();

        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found or does not belong to your company'
            ], 404);
        }

        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Division deleted successfully'
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    /**
     * Display a listing of branches filtered by company
     */
    public function index(Request $request)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $branches = Branch::where('company_id', $companyId)
            ->select('id', 'name', 'company_id', 'branch_address', 'branch_phone')
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Display the specified branch
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $branch = Branch::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your company'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }

    /**
     * Store a newly created branch in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_address' => 'required|string',
            'branch_phone' => 'required|string|max:20',
            'branch_phone_backup' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id

        $branch = Branch::create($validated);

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Branch created successfully'
        ], 201);
    }

    /**
     * Update the specified branch in storage
     */
    public function update(Request $request, $id)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $branch = Branch::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your company'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_address' => 'required|string',
            'branch_phone' => 'required|string|max:20',
            'branch_phone_backup' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $branch->update($validated);

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Branch updated successfully'
        ]);
    }

    /**
     * Remove the specified branch from storage
     */
    public function destroy(Request $request, $id)
    {
        $companyId = $request->get('company_id', 1); // Default ke 1 untuk testing
        // Nanti bisa diganti dengan: Auth::user()->company_id
        
        $branch = Branch::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your company'
            ], 404);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully'
        ]);
    }
}
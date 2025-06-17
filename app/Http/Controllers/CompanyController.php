<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    public function getCompanyForCheckout(Request $request)
    {
        try {
            // 1. DEBUG: Periksa user authentication
            $user = auth()->user();
            Log::info('=== getCompanyForCheckout Debug ===');
            Log::info('Authenticated user:', ['user_id' => $user ? $user->id : null]);

            if (!$user) {
                Log::warning('No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'has_profile' => false,
                    'data' => null
                ], 401);
            }

            // 2. DEBUG: Cari company berdasarkan user_id
            Log::info('Looking for company with user_id:', ['user_id' => $user->id]);

            $company = Company::where('user_id', $user->id)
                ->select('id', 'name', 'email', 'phone', 'user_id')
                ->first();

            Log::info('Company query result:', [
                'found' => $company ? true : false,
                'company_data' => $company ? $company->toArray() : null
            ]);

            // 3. DEBUG: Periksa apakah company ditemukan
            if (!$company) {
                Log::info('No company profile found for user');
                return response()->json([
                    'success' => true,
                    'message' => 'No company profile found',
                    'has_profile' => false,
                    'data' => null
                ], 200);
            }

            // 4. DEBUG: Return data yang ditemukan
            $responseData = [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
            ];

            Log::info('Returning company data:', $responseData);

            return response()->json([
                'success' => true,
                'message' => 'Company profile found',
                'has_profile' => true,
                'data' => $responseData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getCompanyForCheckout:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch company data: ' . $e->getMessage(),
                'has_profile' => false,
                'data' => null
            ], 500);
        }
    }
    // Get authenticated company data
    public function getAuthenticatedCompany()
    {
        try {
            // Gunakan Auth::user() untuk production, User::find(1) untuk development
            $user = Auth::user() ?? User::find(1);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Jika user belum punya company, return data kosong
            if (!$user->company_id) {
                return response()->json([
                    'id' => null,
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'phone_backup' => '',
                    'address' => '',
                    'description' => '',
                    'has_profile' => false
                ]);
            }

            $company = Company::find($user->company_id);

            if (!$company) {
                return response()->json(['error' => 'Company not found'], 404);
            }

            return response()->json([
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->head_office_phone,
                'phone_backup' => $company->head_office_phone_backup,
                'address' => $company->head_office_address,
                'description' => $company->description,
                'has_profile' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting company profile:', $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    // Store company profile
    public function store(Request $request)
    {
        try {
            $user = Auth::user() ?? User::find(1);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'head_office_phone' => 'required|string|max:20',
                'head_office_phone_backup' => 'nullable|string|max:20',
                'head_office_address' => 'required|string|max:500',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if user already has a company
            if ($user->company_id) {
                // Update existing company
                $company = Company::find($user->company_id);
                if ($company) {
                    $company->update($validator->validated());

                    Log::info('Company profile updated:', ['company_id' => $company->id]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Company updated successfully',
                        'data' => [
                            'id' => $company->id,
                            'name' => $company->name,
                            'email' => $company->email,
                            'phone' => $company->head_office_phone,
                            'phone_backup' => $company->head_office_phone_backup,
                            'address' => $company->head_office_address,
                            'description' => $company->description,
                        ]
                    ], 200);
                }
            }

            // Tambahkan validasi unique email hanya jika belum ada company
            $emailValidator = Validator::make($request->all(), [
                'email' => 'unique:companies,email'
            ]);

            if ($emailValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $emailValidator->errors()
                ], 422);
            }

            // Create new company
            $company = Company::create($validator->validated());

            $user->company_id = $company->id;
            $user->save();

            Log::info('New company profile created:', ['company_id' => $company->id]);

            return response()->json([
                'success' => true,
                'message' => 'Company created and linked to user successfully',
                'data' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->head_office_phone,
                    'phone_backup' => $company->head_office_phone_backup,
                    'address' => $company->head_office_address,
                    'description' => $company->description,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing company profile:', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save company profile'
            ], 500);
        }
    }

    // Update company profile
    public function update(Request $request)
    {
        try {
            $user = Auth::user() ?? User::find(1);

            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], 404);
            }

            $company = Company::find($user->company_id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:companies,email,' . $company->id,
                'head_office_phone' => 'required|string|max:20',
                'head_office_phone_backup' => 'nullable|string|max:20',
                'head_office_address' => 'required|string|max:500',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $company->update($validator->validated());

            Log::info('Company profile updated:', ['company_id' => $company->id]);

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->head_office_phone,
                    'phone_backup' => $company->head_office_phone_backup,
                    'address' => $company->head_office_address,
                    'description' => $company->description,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating company profile:', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company profile'
            ], 500);
        }
    }

    public function show($id)
    {
        $company = \App\Models\Company::find($id);
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        return response()->json($company);
    }
}

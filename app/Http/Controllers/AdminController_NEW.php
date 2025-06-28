<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Get authenticated admin profile
     */
    public function profile(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $admin = Admin::with('user')->firstOrCreate(
            ['user_id' => $user->id],
            ['phone_number' => null, 'profile_photo' => null]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'user_id' => $admin->user_id,
                'name' => $admin->user->name,
                'email' => $admin->user->email,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
                'role' => $admin->user->role,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]
        ]);
    }

    /**
     * Update admin profile (phone_number and profile_photo only)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'phone_number' => 'sometimes|nullable|string|max:20',
            'profile_photo' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update or create admin profile
        $admin = Admin::updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['phone_number', 'profile_photo'])
        );

        // Load user relationship
        $admin->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Admin profile updated successfully',
            'data' => [
                'id' => $admin->id,
                'user_id' => $admin->user_id,
                'name' => $admin->user->name,
                'email' => $admin->user->email,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
                'role' => $admin->user->role,
                'updated_at' => $admin->updated_at,
            ]
        ]);
    }

    /**
     * Get all admins (admin only)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $query = Admin::with('user');

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $admins = $query->orderBy('created_at', 'desc')->paginate(10);

        $adminData = $admins->getCollection()->map(function ($admin) {
            return [
                'id' => $admin->id,
                'user_id' => $admin->user_id,
                'name' => $admin->user->name,
                'email' => $admin->user->email,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
                'role' => $admin->user->role,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $adminData,
            'pagination' => [
                'current_page' => $admins->currentPage(),
                'last_page' => $admins->lastPage(),
                'per_page' => $admins->perPage(),
                'total' => $admins->total(),
            ]
        ]);
    }

    /**
     * Get specific admin by ID (admin only)
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $admin = Admin::with('user')->find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'user_id' => $admin->user_id,
                'name' => $admin->user->name,
                'email' => $admin->user->email,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
                'role' => $admin->user->role,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]
        ]);
    }
}

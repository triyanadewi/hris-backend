<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class TopBarController extends Controller
{
    // Ambil data profil admin yang login
    public function profile(Request $request)
    {
        $admin = Auth::user();

        if (!$admin instanceof Admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'admin' => $admin
        ]);
    }

    // Update data profil
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::where('email', $request->identifier)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $admin
        ]);
    }

    // Logout admin
    public function logout(Request $request)
    {
        $user = $request->user(); // pastikan ini instance dari Admin

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete(); // <--- delete() hanya bisa dipanggil jika ini adalah token model
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}

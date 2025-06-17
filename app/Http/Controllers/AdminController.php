<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function profile(Request $request)
    {
        $admin = Auth::guard('sanctum')->user(); // gunakan Sanctum guard
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'first_name' => $admin->name, // jika kamu simpan nama lengkap di 'name'
            'photo' => $admin->profile_photo,
            'email' => $admin->email,
        ]);
    }
}

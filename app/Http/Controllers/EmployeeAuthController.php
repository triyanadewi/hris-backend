<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmployeeAuthController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Check if the authenticated user has the 'employee' role
        if (Auth::user()->role !== 'employee') {
            Auth::logout(); // Log out if not an employee
            throw ValidationException::withMessages([
                'email' => 'You do not have employee access.',
            ]);
        }

        $request->session()->regenerate();

        // For API authentication (if using Sanctum for Next.js)
        $token = $request->user()->createToken('employee-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $request->user(),
            'employee' => $request->user()->employee, // Eager load employee data
            'token' => $token,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
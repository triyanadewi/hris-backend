<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Laravel\Socialite\Facades\Socialite;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user first
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'admin',
        ]);

        // Create admin profile
        $admin = Admin::create([
            'user_id'      => $user->id,
            'phone_number' => $request->phone_number,
        ]);

        return response()->json([
            'message' => 'Admin registered successfully!',
            'admin'   => [
                'id'    => $admin->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find user with admin role
        $user = User::where('email', $request->email)
                   ->where('role', 'admin')
                   ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Admin account not found with this email address.',
                'error' => 'admin_not_found'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided password is incorrect.',
                'error' => 'invalid_password'
            ], 401);
        }

        // Get admin profile (create if doesn't exist)
        $admin = Admin::where('user_id', $user->id)->first();
        
        if (!$admin) {
            // Create admin profile if it doesn't exist
            $admin = Admin::create([
                'user_id' => $user->id,
                'phone_number' => null,
                'profile_photo' => null,
            ]);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => [
                'id'         => $admin->user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'photo'      => $admin->profile_photo,
                'isProfileCompany' => $user->isProfileCompany,
            ]
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Get the authenticated admin's profile
     */
    public function profile(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user has admin role
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied. Admin role required.'], 403);
        }

        // Get admin profile
        $admin = Admin::where('user_id', $user->id)->first();
        
        // Create admin profile if it doesn't exist
        if (!$admin) {
            $admin = Admin::create([
                'user_id' => $user->id,
                'phone_number' => null,
                'profile_photo' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
            ]
        ]);
    }

    /**
     * Change password for authenticated admin
     */
    public function changePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['The current password is incorrect.']]
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke all tokens to force re-login
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password changed successfully. Please login again.'
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user exists and has admin role
        $user = User::where('email', $request->email)
                   ->where('role', 'admin')
                   ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Admin account not found with this email address.'
            ], 404);
        }

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.'
            ]);
        }

        return response()->json([
            'message' => 'Unable to send password reset link.'
        ], 500);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successfully.'
            ]);
        }

        return response()->json([
            'message' => 'Invalid token or email.'
        ], 422);
    }

    /**
     * Check if user is authenticated
     */
    public function me(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user has admin role
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied. Admin role required.'], 403);
        }

        // Get admin profile
        $admin = Admin::where('user_id', $user->id)->first();
        
        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'admin' => $admin ? [
                'id' => $admin->id,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
            ] : null
        ]);
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists
            $user = User::where('email', $googleUser->email)->first();
            
            if ($user) {
                // Check if user is admin
                if ($user->role !== 'admin') {
                    return response()->json([
                        'message' => 'Access denied. Admin role required.'
                    ], 403);
                }
                
                // Get or create admin profile
                $admin = Admin::where('user_id', $user->id)->first();
                if (!$admin) {
                    $admin = Admin::create([
                        'user_id' => $user->id,
                        'phone_number' => null,
                        'profile_photo' => $googleUser->avatar,
                    ]);
                }
                
                $token = $user->createToken('admin-token')->plainTextToken;
                
                return response()->json([
                    'token' => $token,
                    'admin' => [
                        'id' => $admin->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'photo' => $admin->profile_photo,
                    ]
                ]);
            } else {
                // Create new user and admin profile
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(24)), // Random password
                    'role' => 'admin',
                ]);
                
                $admin = Admin::create([
                    'user_id' => $user->id,
                    'phone_number' => null,
                    'profile_photo' => $googleUser->avatar,
                ]);
                
                $token = $user->createToken('admin-token')->plainTextToken;
                
                return response()->json([
                    'token' => $token,
                    'admin' => [
                        'id' => $admin->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'photo' => $admin->profile_photo,
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('admin-token')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'message' => 'Token refreshed successfully'
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Delete all tokens
        $user->tokens()->delete();
        
        return response()->json([
            'message' => 'Logged out from all devices successfully'
        ]);
    }
}

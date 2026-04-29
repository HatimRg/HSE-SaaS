<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            Log::warning('Failed login attempt', [
                'email' => $validated['email'],
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('Invalid credentials', 401);
        }

        if (!$user->isActive()) {
            return $this->errorResponse('Account is inactive', 403);
        }

        // Update last login
        $user->updateLastLogin($request->ip());

        // Create token
        $token = $user->createToken('api-token', ['*'], 
            $validated['remember'] ? now()->addDays(30) : now()->addHours(12)
        )->plainTextToken;

        // Log activity
        $this->logActivity('login', $user, ['ip' => $request->ip()]);

        return $this->successResponse([
            'token' => $token,
            'user' => $this->formatUser($user),
            'must_change_password' => $user->mustChangePassword(),
        ], 'Login successful');
    }

    /**
     * Logout user and revoke token.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Log activity
        $this->logActivity('logout', $user);

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request)
    {
        return $this->successResponse($this->formatUser($request->user()));
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'language' => 'sometimes|in:fr,en',
            'timezone' => 'sometimes|string',
        ]);

        $user->update($validated);

        // Update company settings if language changed
        if (isset($validated['language'])) {
            $settings = $user->company->getSettings();
            $settings['language'] = $validated['language'];
            $user->company->update(['settings' => $settings]);
        }

        $this->logActivity('profile_updated', $user, $validated);

        return $this->successResponse($this->formatUser($user), 'Profile updated successfully');
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        $this->logActivity('password_changed', $user);

        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * Forgot password - send reset link.
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate reset token
        $token = \Illuminate\Support\Str::random(60);
        \DB::table('password_resets')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send email (placeholder)
        // Mail::to($validated['email'])->send(new PasswordResetMail($token));

        Log::info('Password reset requested', ['email' => $validated['email']]);

        return $this->successResponse(null, 'Password reset link sent');
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = \DB::table('password_resets')
            ->where('email', $validated['email'])
            ->first();

        if (!$reset || !Hash::check($validated['token'], $reset->token)) {
            return $this->errorResponse('Invalid or expired token', 400);
        }

        // Update password
        User::where('email', $validated['email'])->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // Delete reset token
        \DB::table('password_resets')->where('email', $validated['email'])->delete();

        return $this->successResponse(null, 'Password reset successfully');
    }

    /**
     * Format user data for response.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name, // Computed from first_name + last_name
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->getAvatarUrl(),
            'role' => $user->role?->only(['id', 'name', 'display_name']),
            'company' => $user->company?->only(['id', 'name', 'color_primary_light', 'color_primary_dark', 'color_background_light', 'color_background_dark', 'color_accent']),
            'project_access' => [
                'type' => $user->project_access_type,
                'pole_id' => $user->pole_id,
                'has_all_access' => $user->hasAllProjectsAccess(),
                'has_pole_access' => $user->hasPoleAccess(),
                'has_specific_projects' => $user->hasSpecificProjectsAccess(),
            ],
            'language' => $user->language,
            'timezone' => $user->timezone,
            'must_change_password' => $user->mustChangePassword(),
            'permissions' => $this->getUserPermissions($user),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    /**
     * Get user permissions.
     */
    private function getUserPermissions(User $user): array
    {
        $roleName = $user->role?->name ?? '';
        
        return [
            'is_admin' => $roleName === 'admin',
            'is_admin_like' => in_array($roleName, Role::getAdminRoles()),
            'is_hse' => in_array($roleName, Role::getHseRoles()),
            'can_approve_kpi' => in_array($roleName, ['admin', 'hse_director', 'hse_manager']),
            'can_approve_permit' => in_array($roleName, ['admin', 'hse_director', 'hse_manager', 'responsable']),
            'can_manage_users' => $roleName === 'admin',
            'can_export' => in_array($roleName, ['admin', 'hse_director', 'engineer']),
        ];
    }
}

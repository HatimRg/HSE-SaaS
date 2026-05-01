<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $users = User::with(['company', 'roles'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->company_id, function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->successResponse($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company_id' => 'required|exists:companies,id',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'job_title' => 'nullable|string|max:255',
            'language' => 'nullable|string|in:fr,en',
            'timezone' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $validated['company_id'],
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'timezone' => $validated['timezone'] ?? 'Europe/Paris',
            'is_active' => true,
        ]);

        // Assign role
        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        $user->load(['company', 'roles']);

        $this->logActivity('user_created', $user, $validated);

        return $this->successResponse($user, 'User created successfully', 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['company', 'roles', 'projects'])->findOrFail($id);

        return $this->successResponse($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'company_id' => 'sometimes|exists:companies,id',
            'phone' => 'nullable|string|max:20',
            'job_title' => 'nullable|string|max:255',
            'language' => 'nullable|string|in:fr,en',
            'timezone' => 'nullable|string',
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        $user->update(collect($validated)->except('role')->toArray());

        // Update role if provided
        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }

        $user->load(['company', 'roles']);

        $this->logActivity('user_updated', $user, $validated);

        return $this->successResponse($user, 'User updated successfully');
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Soft delete or deactivate instead of hard delete
        $user->update(['is_active' => false]);
        $user->delete();

        $this->logActivity('user_deleted', $user);

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Activate a user.
     */
    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true]);

        $this->logActivity('user_activated', $user);

        return $this->successResponse($user, 'User activated successfully');
    }

    /**
     * Deactivate a user.
     */
    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        $this->logActivity('user_deactivated', $user);

        return $this->successResponse($user, 'User deactivated successfully');
    }
}

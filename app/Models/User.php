<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar',
        'role_id',
        'project_access_type', // 'all', 'pole', 'projects'
        'pole_id', // For pole-level access
        'must_change_password',
        'language',
        'timezone',
        'last_login_at',
        'last_login_ip',
        'is_active',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The relations to eager load on every query.
     */
    protected $with = ['role'];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Note: Tenant scoping is handled by TenantMiddleware.
        // User model needs its own scope since it doesn't extend BaseModel.
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check() && !auth()->user()->isSuperAdmin()) {
                $builder->where('users.company_id', auth()->user()->company_id);
            }
        });
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the role associated with the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the pole associated with the user.
     */
    public function pole(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'pole_id');
    }

    /**
     * Get projects the user is specifically assigned to (for 'projects' access type).
     */
    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_projects')
                    ->withTimestamps();
    }

    /**
     * Get all projects the user can access based on project_access_type.
     */
    public function accessibleProjects()
    {
        return match($this->project_access_type) {
            'all' => Project::where('company_id', $this->company_id),
            'pole' => Project::where('company_id', $this->company_id)
                            ->where('pole_id', $this->pole_id),
            'projects' => $this->assignedProjects(),
            default => Project::where('company_id', $this->company_id)->whereNull('id'), // Empty
        };
    }

    /**
     * Get user sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get activity logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'admin' && $this->email === config('app.super_admin_email');
    }

    /**
     * Check if user must change password.
     */
    public function mustChangePassword(): bool
    {
        return $this->must_change_password;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->company?->isSubscriptionActive();
    }

    /**
     * Get display name.
     */
    public function getDisplayName(): string
    {
        $name = trim("{$this->first_name} {$this->last_name}");
        return $name ?: explode('@', $this->email)[0];
    }

    /**
     * Get full name attribute.
     */
    public function getNameAttribute(): string
    {
        return $this->getDisplayName();
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->getDisplayName()) . '&background=random';
    }

    /**
     * Update last login.
     */
    public function updateLastLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    /**
     * Scope: Active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->whereHas('role', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Check if user has access to all projects.
     */
    public function hasAllProjectsAccess(): bool
    {
        return $this->project_access_type === 'all';
    }

    /**
     * Check if user has pole-level access.
     */
    public function hasPoleAccess(): bool
    {
        return $this->project_access_type === 'pole' && $this->pole_id;
    }

    /**
     * Check if user has specific projects access.
     */
    public function hasSpecificProjectsAccess(): bool
    {
        return $this->project_access_type === 'projects';
    }

    /**
     * Check if user can access a specific project.
     */
    public function canAccessProject(int $projectId): bool
    {
        return match($this->project_access_type) {
            'all' => true,
            'pole' => Project::where('id', $projectId)->where('pole_id', $this->pole_id)->exists(),
            'projects' => $this->assignedProjects()->where('projects.id', $projectId)->exists(),
            default => false,
        };
    }
}

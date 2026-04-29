<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Policies\CompanyPolicy;
use App\Policies\ProjectPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Project::class => ProjectPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super Admin gate
        Gate::define('super-admin', function (User $user) {
            return $user->role?->name === 'admin' && $user->is_super_admin;
        });

        // Company Admin gate
        Gate::define('company-admin', function (User $user) {
            return in_array($user->role?->name, ['admin', 'hse_director', 'hr_director']);
        });

        // Project access gate
        Gate::define('access-project', function (User $user, Project $project) {
            return $user->company_id === $project->company_id &&
                   ($user->projects->contains($project->id) || $this->isAdminLike($user));
        });

        // Admin-like roles gate
        Gate::define('admin-like', function (User $user) {
            return $this->isAdminLike($user);
        });

        // HSE Manager gate
        Gate::define('hse-manager', function (User $user) {
            return in_array($user->role?->name, [
                'admin', 'hse_director', 'hse_manager', 'regional_hse_manager', 'responsable'
            ]);
        });
    }

    /**
     * Check if user has admin-like privileges
     */
    private function isAdminLike(User $user): bool
    {
        return in_array($user->role?->name, [
            'admin', 'hse_director', 'hr_director', 'pole_director',
            'project_director', 'regional_hse_manager', 'responsable'
        ]);
    }
}

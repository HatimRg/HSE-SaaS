<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Company;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get company from authenticated user
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }

        // Skip tenant isolation for super admin
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $company = $user->company;
        
        if (!$company) {
            abort(403, 'No company assigned to user');
        }

        // Set tenant context
        $this->setTenantContext($company);

        // Apply database query scopes for data isolation
        $this->applyDataIsolation($company);

        return $next($request);
    }

    /**
     * Set tenant context for the request
     */
    private function setTenantContext(Company $company)
    {
        // Store company in request for easy access
        request()->merge(['tenant_company' => $company]);

        // Note: Do NOT modify config at runtime - it's not thread-safe
        // and can leak between requests. Use request()->attributes instead.
        request()->attributes->set('tenant_id', $company->id);
    }

    /**
     * Apply data isolation to database queries
     */
    private function applyDataIsolation(Company $company)
    {
        // Add global scopes to models for tenant isolation
        $models = [
            'App\Models\Project',
            'App\Models\Worker',
            'App\Models\KpiReport',
            'App\Models\SorReport',
            'App\Models\WorkPermit',
            'App\Models\Inspection',
            'App\Models\TrainingSession',
            'App\Models\PpeItem',
            'App\Models\LibraryDocument',
            'App\Models\LibraryFolder',
            'App\Models\CommunityPost',
            'App\Models\IncidentInvestigation',
            'App\Models\RiskAssessment',
        ];

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::addGlobalScope('tenant', function ($query) use ($company) {
                    $query->where('company_id', $company->id);
                });
            }
        }
    }
}

<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        $userCompanyId = $user->company_id;

        // Set tenant context in application
        app()->instance('current_tenant_id', $userCompanyId);
        app()->instance('current_user', $user);

        // Verify tenant match for resource access
        if ($request->route('id') || $request->route('company_id')) {
            $requestedCompanyId = $request->route('company_id') ?? 
                                  $this->extractCompanyIdFromResource($request);
            
            if ($requestedCompanyId && $requestedCompanyId != $userCompanyId) {
                Log::warning('Tenant isolation violation', [
                    'user_id' => $user->id,
                    'user_company' => $userCompanyId,
                    'requested_company' => $requestedCompanyId,
                    'route' => $request->route()->getName(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Access denied: Tenant mismatch',
                ], 403);
            }
        }

        // Add tenant context to response headers (for debugging in dev)
        $response = $next($request);
        
        if (config('app.debug')) {
            $response->headers->set('X-Tenant-ID', $userCompanyId);
        }

        return $response;
    }

    /**
     * Extract company ID from request resource
     */
    private function extractCompanyIdFromResource(Request $request): ?int
    {
        // Check if the request has a project_id and look up its company
        if ($projectId = $request->input('project_id')) {
            $project = \App\Models\Project::find($projectId);
            return $project?->company_id;
        }

        // Check resource parameters
        $resourceId = $request->route('id');
        $resourceType = $request->route('resource');

        if ($resourceId && $resourceType) {
            $model = $this->getModelForResource($resourceType);
            if ($model) {
                $resource = $model::find($resourceId);
                return $resource?->company_id;
            }
        }

        return null;
    }

    /**
     * Get model class for resource type
     */
    private function getModelForResource(string $resource): ?string
    {
        $models = [
            'projects' => \App\Models\Project::class,
            'workers' => \App\Models\Worker::class,
            'kpi-reports' => \App\Models\KpiReport::class,
            'sor-reports' => \App\Models\SorReport::class,
            'inspections' => \App\Models\Inspection::class,
            'work-permits' => \App\Models\WorkPermit::class,
            'training-sessions' => \App\Models\TrainingSession::class,
            'machines' => \App\Models\Machine::class,
        ];

        return $models[$resource] ?? null;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\User;
use App\Models\Project;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SuperAdminController extends BaseController
{
    /**
     * Get super admin dashboard data
     */
    public function dashboard(Request $request)
    {
        // Global statistics
        $stats = [
            'total_companies' => Company::count(),
            'total_users' => User::count(),
            'total_projects' => Project::count(),
            'active_users_today' => User::whereDate('last_login_at', today())->count(),
            'total_revenue' => Subscription::where('status', 'active')->sum('amount'),
            'concurrent_users' => $this->getConcurrentUserCount(),
            'system_health' => $this->getSystemHealth(),
        ];

        // Company performance
        $companies = Company::with(['users', 'projects'])
            ->withCount(['users', 'projects'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Recent activity
        $recentActivity = $this->getRecentActivity();

        // System metrics
        $systemMetrics = $this->getSystemMetrics();

        return $this->successResponse([
            'stats' => $stats,
            'companies' => $companies,
            'recent_activity' => $recentActivity,
            'system_metrics' => $systemMetrics,
        ]);
    }

    /**
     * Get all companies with filtering
     */
    public function companies(Request $request)
    {
        $query = Company::with(['users', 'projects', 'subscription'])
            ->withCount(['users', 'projects']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->plan) {
            $query->whereHas('subscription', function ($q) use ($request) {
                $q->where('plan', $request->plan);
            });
        }

        // Search
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $companies = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return $this->successResponse($companies);
    }

    /**
     * Create new company
     */
    public function createCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:companies',
            'admin_email' => 'required|email|unique:users,email',
            'admin_name' => 'required|string|max:255',
            'plan' => 'required|in:basic,professional,enterprise',
            'max_users' => 'required|integer|min:1',
            'max_projects' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Create company
            $company = Company::create([
                'name' => $validated['name'],
                'domain' => $validated['domain'],
                'status' => 'active',
                'max_users' => $validated['max_users'],
                'max_projects' => $validated['max_projects'],
            ]);

            // Create admin user
            $admin = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => bcrypt('tempPassword123'), // Should be changed on first login
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ]);

            // Assign admin role
            $admin->assignRole('company_admin');

            // Create subscription
            Subscription::create([
                'company_id' => $company->id,
                'plan' => $validated['plan'],
                'status' => 'active',
                'amount' => $this->getPlanPrice($validated['plan']),
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);

            DB::commit();

            return $this->successResponse($company->load(['users', 'subscription']), 'Company created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create company', 500);
        }
    }

    /**
     * Update company
     */
    public function updateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255|unique:companies,domain,' . $id,
            'status' => 'sometimes|in:active,inactive,suspended',
            'max_users' => 'sometimes|integer|min:1',
            'max_projects' => 'sometimes|integer|min:1',
        ]);

        $company->update($validated);

        return $this->successResponse($company->fresh(), 'Company updated successfully');
    }

    /**
     * Delete company
     */
    public function deleteCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        // Soft delete to maintain data integrity
        $company->delete();

        return $this->successResponse(null, 'Company deleted successfully');
    }

    /**
     * Get company users
     */
    public function companyUsers(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        $users = $company->users()
            ->with(['roles'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return $this->successResponse($users);
    }

    /**
     * Get system health metrics
     */
    private function getSystemHealth()
    {
        return [
            'database_status' => $this->checkDatabaseHealth(),
            'cache_status' => $this->checkCacheHealth(),
            'storage_status' => $this->checkStorageHealth(),
            'api_response_time' => $this->getApiResponseTime(),
            'error_rate' => $this->getErrorRate(),
        ];
    }

    /**
     * Get concurrent user count
     */
    private function getConcurrentUserCount()
    {
        return Cache::get('concurrent_users', 0);
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity()
    {
        return DB::table('activity_log')
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->join('companies', 'users.company_id', '=', 'companies.id')
            ->select('activity_log.*', 'users.name as user_name', 'companies.name as company_name')
            ->orderBy('activity_log.created_at', 'desc')
            ->take(50)
            ->get();
    }

    /**
     * Get system metrics
     */
    private function getSystemMetrics()
    {
        return [
            'cpu_usage' => sys_getloadavg()[0] ?? 0,
            'memory_usage' => memory_get_usage(true),
            'disk_usage' => disk_free_space('/'),
            'active_sessions' => DB::table('sessions')->count(),
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth()
    {
        try {
            DB::select('SELECT 1');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth()
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $value = Cache::get('health_check');
            return $value === 'ok' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth()
    {
        $freeSpace = disk_free_space(storage_path());
        $totalSpace = disk_total_space(storage_path());
        $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

        return $usagePercent < 90 ? 'healthy' : 'warning';
    }

    /**
     * Get API response time
     */
    private function getApiResponseTime()
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2); // in milliseconds
    }

    /**
     * Get error rate
     */
    private function getErrorRate()
    {
        $total = Cache::get('api_requests_total', 0);
        $errors = Cache::get('api_errors_total', 0);
        
        return $total > 0 ? round(($errors / $total) * 100, 2) : 0;
    }

    /**
     * Get cache hit rate
     */
    private function getCacheHitRate()
    {
        $hits = Cache::get('cache_hits', 0);
        $misses = Cache::get('cache_misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Get plan price
     */
    private function getPlanPrice($plan)
    {
        $prices = [
            'basic' => 99,
            'professional' => 299,
            'enterprise' => 999,
        ];

        return $prices[$plan] ?? 99;
    }

    /**
     * Switch to company context (for super admin)
     */
    public function switchCompany(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        // Store company context in session
        session(['super_admin_company_context' => $companyId]);

        return $this->successResponse($company, 'Switched to company context');
    }

    /**
     * Clear company context (return to super admin view)
     */
    public function clearCompanyContext(Request $request)
    {
        session()->forget('super_admin_company_context');

        return $this->successResponse(null, 'Cleared company context');
    }

    /**
     * Get system logs
     */
    public function systemLogs(Request $request)
    {
        $logs = DB::table('system_logs')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 100);

        return $this->successResponse($logs);
    }

    /**
     * Send broadcast to all companies
     */
    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,warning,error,success',
            'target_companies' => 'nullable|array',
            'target_companies.*' => 'exists:companies,id',
        ]);

        // Create broadcast notification
        $broadcast = DB::table('broadcasts')->insert([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'],
            'target_companies' => json_encode($validated['target_companies'] ?? []),
            'created_at' => now(),
        ]);

        return $this->successResponse($broadcast, 'Broadcast sent successfully');
    }

    /**
     * Get super admin statistics.
     */
    public function stats()
    {
        $stats = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_active', true)->count(),
            'total_users' => \App\Models\User::count(),
            'total_projects' => \App\Models\Project::count(),
            'total_workers' => \App\Models\Worker::count(),
            'total_incidents' => \App\Models\SorReport::count(),
            'total_inspections' => \App\Models\Inspection::count(),
            'total_kpi_reports' => \App\Models\KpiReport::count(),
            'total_work_permits' => \App\Models\WorkPermit::count(),
            'total_training_sessions' => \App\Models\TrainingSession::count(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Get audit logs.
     */
    public function auditLogs(Request $request)
    {
        $query = \Spatie\Activitylog\Models\Activity::with(['causer', 'subject']);

        if ($request->log_name) {
            $query->where('log_name', $request->log_name);
        }
        if ($request->causer_id) {
            $query->where('causer_id', $request->causer_id);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25);

        return $this->successResponse($logs);
    }

    /**
     * Suspend a company.
     */
    public function suspendCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update(['is_active' => false]);

        $this->logActivity('company_suspended', $company);

        return $this->successResponse($company, 'Company suspended successfully');
    }

    /**
     * Activate a company.
     */
    public function activateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update(['is_active' => true]);

        $this->logActivity('company_activated', $company);

        return $this->successResponse($company, 'Company activated successfully');
    }
}

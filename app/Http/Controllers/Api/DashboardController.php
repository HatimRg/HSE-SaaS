<?php

namespace App\Http\Controllers\Api;

use App\Models\DailyHeadcount;
use App\Models\Inspection;
use App\Models\KpiReport;
use App\Models\SorReport;
use App\Models\WorkPermit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    /**
     * Get dashboard overview.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $projectId = $request->get('project_id');
        $period = $request->get('period', 'month'); // week, month, quarter, year

        $cacheKey = "dashboard:{$period}:" . ($projectId ?? 'all');

        $data = $this->cache->remember($cacheKey, function () use ($companyId, $projectId, $period) {
            $dateRange = $this->getDateRange($period);
            
            return [
                'summary' => $this->getSummary($companyId, $projectId, $dateRange),
                'safety_metrics' => $this->getSafetyMetrics($companyId, $projectId, $dateRange),
                'compliance' => $this->getComplianceMetrics($companyId, $projectId),
                'recent_activity' => $this->getRecentActivity($companyId, $projectId),
                'alerts' => $this->getAlerts($companyId, $projectId),
            ];
        }, 120);

        return $this->successResponse($data);
    }

    /**
     * Get dashboard statistics.
     */
    public function stats(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        $stats = [
            'projects' => \App\Models\Project::where('company_id', $companyId)->where('status', 'active')->count(),
            'workers' => \App\Models\Worker::where('company_id', $companyId)->where('status', 'active')->count(),
            'open_sors' => SorReport::where('company_id', $companyId)->whereIn('status', ['open', 'in-progress'])->count(),
            'active_permits' => WorkPermit::where('company_id', $companyId)->where('status', 'approved')->where('expiry_date', '>', now())->count(),
            'upcoming_inspections' => Inspection::where('company_id', $companyId)->whereBetween('next_inspection_date', [now(), now()->addDays(7)])->count(),
            'training_sessions' => \App\Models\TrainingSession::where('company_id', $companyId)->where('start_date', '>', now())->count(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Get dashboard charts data.
     */
    public function charts(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $theme = $request->get('theme', 'safety'); // safety, training, compliance, ppe, environmental
        $period = $request->get('period', 'month');

        $dateRange = $this->getDateRange($period);

        $data = match ($theme) {
            'safety' => $this->getSafetyCharts($companyId, $dateRange),
            'training' => $this->getTrainingCharts($companyId, $dateRange),
            'compliance' => $this->getComplianceCharts($companyId, $dateRange),
            'ppe' => $this->getPpeCharts($companyId),
            'environmental' => $this->getEnvironmentalCharts($companyId, $dateRange),
            default => $this->getSafetyCharts($companyId, $dateRange),
        };

        return $this->successResponse($data);
    }

    /**
     * Get date range for period.
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
        };
    }

    /**
     * Get summary statistics.
     */
    private function getSummary(int $companyId, ?int $projectId, array $dateRange): array
    {
        $baseQuery = function ($model) use ($companyId, $projectId) {
            $query = $model::where('company_id', $companyId);
            if ($projectId) {
                $query->where('project_id', $projectId);
            }
            return $query;
        };

        return [
            'total_man_hours' => $baseQuery(KpiReport::class)
                ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])
                ->sum('total_hours'),
            'total_injuries' => $baseQuery(KpiReport::class)
                ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])
                ->sum('injuries'),
            'open_observations' => $baseQuery(SorReport::class)
                ->whereIn('status', ['open', 'in-progress'])
                ->count(),
            'inspection_score' => $baseQuery(Inspection::class)
                ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->avg('score') ?? 0,
        ];
    }

    /**
     * Get safety metrics.
     */
    private function getSafetyMetrics(int $companyId, ?int $projectId, array $dateRange): array
    {
        $query = KpiReport::where('company_id', $companyId)
            ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']]);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $totals = $query->select(
            DB::raw('SUM(total_hours) as total_hours'),
            DB::raw('SUM(injuries) as total_injuries'),
            DB::raw('SUM(first_aids) as total_first_aids'),
            DB::raw('SUM(near_misses) as total_near_misses'),
            DB::raw('SUM(observations) as total_observations')
        )->first();

        $totalHours = $totals->total_hours ?? 1;

        return [
            'trir' => round(($totals->total_injuries * 200000) / $totalHours, 2),
            'frequency_rate' => round(($totals->total_injuries * 1000000) / $totalHours, 2),
            'severity_rate' => 0, // Would need days lost calculation
            'total_man_hours' => round($totals->total_hours ?? 0, 2),
            'near_miss_rate' => round(($totals->total_near_misses / max($totals->total_injuries, 1)), 2),
        ];
    }

    /**
     * Get compliance metrics.
     */
    private function getComplianceMetrics(int $companyId, ?int $projectId): array
    {
        $workPermitQuery = WorkPermit::where('company_id', $companyId);
        if ($projectId) {
            $workPermitQuery->where('project_id', $projectId);
        }

        $inspectionQuery = Inspection::where('company_id', $companyId);
        if ($projectId) {
            $inspectionQuery->where('project_id', $projectId);
        }

        return [
            'valid_permits' => $workPermitQuery->where('status', 'approved')->where('expiry_date', '>', now())->count(),
            'expired_permits' => $workPermitQuery->where('expiry_date', '<', now())->count(),
            'permits_pending' => $workPermitQuery->where('status', 'pending')->count(),
            'inspection_pass_rate' => $inspectionQuery->where('result', 'pass')->count() / max($inspectionQuery->count(), 1) * 100,
            'failed_inspections' => $inspectionQuery->where('result', 'fail')->count(),
        ];
    }

    /**
     * Get recent activity.
     */
    private function getRecentActivity(int $companyId, ?int $projectId): array
    {
        $activity = [];

        // Recent SORs
        $sorQuery = SorReport::where('company_id', $companyId)->with(['reporter', 'project']);
        if ($projectId) {
            $sorQuery->where('project_id', $projectId);
        }
        $recentSors = $sorQuery->latest()->limit(5)->get();

        foreach ($recentSors as $sor) {
            $activity[] = [
                'type' => 'sor',
                'title' => $sor->title,
                'description' => "New SOR reported by {$sor->reporter?->name}",
                'project' => $sor->project?->name,
                'date' => $sor->created_at->toIso8601String(),
                'status' => $sor->status,
                'severity' => $sor->severity,
            ];
        }

        // Recent KPIs
        $kpiQuery = KpiReport::where('company_id', $companyId)->with(['creator', 'project']);
        if ($projectId) {
            $kpiQuery->where('project_id', $projectId);
        }
        $recentKpis = $kpiQuery->latest()->limit(3)->get();

        foreach ($recentKpis as $kpi) {
            $activity[] = [
                'type' => 'kpi',
                'title' => 'KPI Report Submitted',
                'description' => "Report for {$kpi->project?->name} by {$kpi->creator?->name}",
                'project' => $kpi->project?->name,
                'date' => $kpi->created_at->toIso8601String(),
                'status' => $kpi->status,
            ];
        }

        // Sort by date
        usort($activity, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return array_slice($activity, 0, 10);
    }

    /**
     * Get alerts.
     */
    private function getAlerts(int $companyId, ?int $projectId): array
    {
        $alerts = [];

        // Expiring permits
        $permitQuery = WorkPermit::where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereBetween('expiry_date', [now(), now()->addDays(3)]);
        if ($projectId) {
            $permitQuery->where('project_id', $projectId);
        }
        $expiringPermits = $permitQuery->count();

        if ($expiringPermits > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Permits Expiring Soon',
                'message' => "{$expiringPermits} work permit(s) expire within 3 days",
                'action_url' => '/work-permits',
            ];
        }

        // Overdue SORs
        $sorQuery = SorReport::where('company_id', $companyId)
            ->whereIn('status', ['open', 'in-progress'])
            ->where('due_date', '<', now());
        if ($projectId) {
            $sorQuery->where('project_id', $projectId);
        }
        $overdueSors = $sorQuery->count();

        if ($overdueSors > 0) {
            $alerts[] = [
                'type' => 'urgent',
                'title' => 'Overdue Safety Observations',
                'message' => "{$overdueSors} SOR(s) are overdue",
                'action_url' => '/sor-reports',
            ];
        }

        // Medical fitness expiring
        $medicalQuery = \App\Models\Worker::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('medical_fitness_date', '<=', now()->subYear()->addDays(30));
        if ($projectId) {
            // Workers assigned to project would need a join
        }
        $medicalExpiring = $medicalQuery->count();

        if ($medicalExpiring > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Medical Fitness Expiring',
                'message' => "{$medicalExpiring} worker(s) have medical fitness expiring within 30 days",
                'action_url' => '/workers',
            ];
        }

        return $alerts;
    }

    /**
     * Get safety charts data.
     */
    private function getSafetyCharts(int $companyId, array $dateRange): array
    {
        // Monthly incidents trend
        $monthlyData = KpiReport::where('company_id', $companyId)
            ->whereBetween('period_start', [$dateRange['start']->copy()->subMonths(11), $dateRange['end']])
            ->select(
                DB::raw('DATE_FORMAT(period_start, "%Y-%m") as month'),
                DB::raw('SUM(injuries) as injuries'),
                DB::raw('SUM(near_misses) as near_misses'),
                DB::raw('SUM(observations) as observations')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'incidents_trend' => [
                'labels' => $monthlyData->pluck('month'),
                'datasets' => [
                    ['label' => 'Injuries', 'data' => $monthlyData->pluck('injuries')],
                    ['label' => 'Near Misses', 'data' => $monthlyData->pluck('near_misses')],
                    ['label' => 'Observations', 'data' => $monthlyData->pluck('observations')],
                ],
            ],
            'sor_status' => [
                'labels' => ['Open', 'In Progress', 'Closed'],
                'data' => [
                    SorReport::where('company_id', $companyId)->where('status', 'open')->count(),
                    SorReport::where('company_id', $companyId)->where('status', 'in-progress')->count(),
                    SorReport::where('company_id', $companyId)->where('status', 'closed')->count(),
                ],
            ],
        ];
    }

    /**
     * Get training charts data.
     */
    private function getTrainingCharts(int $companyId, array $dateRange): array
    {
        return [
            'sessions_by_type' => \App\Models\TrainingSession::where('company_id', $companyId)
                ->whereBetween('start_date', [$dateRange['start'], $dateRange['end']])
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'upcoming_sessions' => \App\Models\TrainingSession::where('company_id', $companyId)
                ->where('start_date', '>', now())
                ->where('start_date', '<', now()->addMonth())
                ->count(),
        ];
    }

    /**
     * Get compliance charts.
     */
    private function getComplianceCharts(int $companyId, array $dateRange): array
    {
        return [
            'inspection_results' => Inspection::where('company_id', $companyId)
                ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->select('result', DB::raw('count(*) as count'))
                ->groupBy('result')
                ->pluck('count', 'result'),
            'permit_status' => WorkPermit::where('company_id', $companyId)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];
    }

    /**
     * Get PPE charts.
     */
    private function getPpeCharts(int $companyId): array
    {
        $categories = \App\Models\PpeItem::where('company_id', $companyId)
            ->where('is_active', true)
            ->withCount('stocks')
            ->get()
            ->groupBy('category');

        return [
            'stock_by_category' => $categories->map(fn($items) => $items->sum(fn($i) => $i->stocks->sum('quantity'))),
            'low_stock_items' => \App\Models\PpeItem::where('company_id', $companyId)
                ->where('is_active', true)
                ->get()
                ->filter(fn($item) => $item->isStockLow())
                ->count(),
        ];
    }

    /**
     * Get environmental charts.
     */
    private function getEnvironmentalCharts(int $companyId, array $dateRange): array
    {
        return [
            'environmental_incidents' => KpiReport::where('company_id', $companyId)
                ->whereBetween('period_start', [$dateRange['start'], $dateRange['end']])
                ->sum('environmental_incidents'),
        ];
    }
}

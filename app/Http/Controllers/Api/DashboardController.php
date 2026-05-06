<?php

namespace App\Http\Controllers\Api;

use App\Models\DailyHeadcount;
use App\Models\EnvironmentalReading;
use App\Models\EventAction;
use App\Models\HseEvent;
use App\Models\Inspection;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\PpeStock;
use App\Models\WorkerDocument;
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
            'open_events' => HseEvent::where('company_id', $companyId)->whereIn('status', ['open', 'in_progress'])->count(),
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
            'total_man_hours' => $baseQuery(DailyHeadcount::class)
                ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->sum('total_count') * 8,
            'total_injuries' => $baseQuery(HseEvent::class)
                ->where('type', 'incident')
                ->whereIn('severity', ['high', 'critical'])
                ->whereBetween('occurred_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'open_observations' => $baseQuery(HseEvent::class)
                ->whereIn('status', ['open', 'in_progress'])
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
        $eventQuery = HseEvent::where('company_id', $companyId)
            ->whereBetween('occurred_at', [$dateRange['start'], $dateRange['end']]);
        if ($projectId) {
            $eventQuery->where('project_id', $projectId);
        }

        $totalIncidents = (clone $eventQuery)->where('type', 'incident')->whereIn('severity', ['high', 'critical'])->count();
        $totalNearMisses = (clone $eventQuery)->where('type', 'near_miss')->count();
        $totalEvents = (clone $eventQuery)->count();

        $headcountQuery = DailyHeadcount::where('company_id', $companyId)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']]);
        if ($projectId) {
            $headcountQuery->where('project_id', $projectId);
        }
        $totalHours = $headcountQuery->sum('total_count') * 8;

        // Also check KPI engine for computed values
        $trirValue = KpiValue::where('company_id', $companyId)
            ->whereHas('definition', fn($q) => $q->where('code', 'trir'))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderByDesc('computed_at')->value('value');

        $ltifrValue = KpiValue::where('company_id', $companyId)
            ->whereHas('definition', fn($q) => $q->where('code', 'ltifr'))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderByDesc('computed_at')->value('value');

        return [
            'trir' => $trirValue ?? ($totalHours > 0 ? round(($totalIncidents * 200000) / $totalHours, 2) : 0),
            'frequency_rate' => $ltifrValue ?? ($totalHours > 0 ? round(($totalIncidents * 1000000) / $totalHours, 2) : 0),
            'severity_rate' => 0,
            'total_man_hours' => round($totalHours, 2),
            'near_miss_rate' => $totalEvents > 0 ? round(($totalNearMisses / $totalEvents) * 100, 1) : 0,
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

        // Recent HSE Events
        $eventQuery = HseEvent::where('company_id', $companyId)->with(['reporter', 'project']);
        if ($projectId) {
            $eventQuery->where('project_id', $projectId);
        }
        $recentEvents = $eventQuery->latest()->limit(5)->get();

        foreach ($recentEvents as $event) {
            $activity[] = [
                'type' => $event->type,
                'title' => $event->title,
                'description' => "Event reported by {$event->reporter?->name}",
                'project' => $event->project?->name,
                'date' => $event->created_at->toIso8601String(),
                'status' => $event->status,
                'severity' => $event->severity,
            ];
        }

        // Recent KPI computations
        $kpiQuery = KpiValue::where('company_id', $companyId)->with(['definition', 'project']);
        if ($projectId) {
            $kpiQuery->where('project_id', $projectId);
        }
        $recentKpis = $kpiQuery->latest('computed_at')->limit(3)->get();

        foreach ($recentKpis as $kpi) {
            $activity[] = [
                'type' => 'kpi',
                'title' => "{$kpi->definition?->name} Computed",
                'description' => "Value: {$kpi->value} for {$kpi->project?->name}",
                'project' => $kpi->project?->name,
                'date' => $kpi->computed_at->toIso8601String(),
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

        // Overdue events
        $eventQuery = HseEvent::where('company_id', $companyId)
            ->whereIn('status', ['open', 'in_progress'])
            ->where('due_date', '<', now());
        if ($projectId) {
            $eventQuery->where('project_id', $projectId);
        }
        $overdueEvents = $eventQuery->count();

        if ($overdueEvents > 0) {
            $alerts[] = [
                'type' => 'urgent',
                'title' => 'Overdue Safety Events',
                'message' => "{$overdueEvents} event(s) are overdue",
                'action_url' => '/hse-events',
            ];
        }

        // Overdue actions
        $actionQuery = EventAction::where('company_id', $companyId)
            ->whereNotIn('status', ['completed', 'verified'])
            ->where('due_date', '<', now());
        $overdueActions = $actionQuery->count();

        if ($overdueActions > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Overdue Actions',
                'message' => "{$overdueActions} corrective/preventive action(s) are overdue",
                'action_url' => '/event-actions',
            ];
        }

        // Worker documents expiring
        $docQuery = WorkerDocument::where('company_id', $companyId)
            ->where('status', 'valid')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)]);
        $docsExpiring = $docQuery->count();

        if ($docsExpiring > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Documents Expiring',
                'message' => "{$docsExpiring} worker document(s) expire within 30 days",
                'action_url' => '/workers',
            ];
        }

        // Environmental exceedances
        $exceedanceQuery = EnvironmentalReading::where('company_id', $companyId)
            ->where('is_exceedance', true)
            ->where('measured_at', '>=', now()->subDay());
        if ($projectId) {
            $exceedanceQuery->where('project_id', $projectId);
        }
        $recentExceedances = $exceedanceQuery->count();

        if ($recentExceedances > 0) {
            $alerts[] = [
                'type' => 'urgent',
                'title' => 'Environmental Exceedances',
                'message' => "{$recentExceedances} environmental threshold exceedance(s) in last 24h",
                'action_url' => '/environment',
            ];
        }

        return $alerts;
    }

    /**
     * Get safety charts data.
     */
    private function getSafetyCharts(int $companyId, array $dateRange): array
    {
        // Monthly events trend from hse_events
        $monthlyData = HseEvent::where('company_id', $companyId)
            ->whereBetween('occurred_at', [$dateRange['start']->copy()->subMonths(11), $dateRange['end']])
            ->select(
                DB::raw('DATE_FORMAT(occurred_at, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN type = "incident" THEN 1 ELSE 0 END) as incidents'),
                DB::raw('SUM(CASE WHEN type = "near_miss" THEN 1 ELSE 0 END) as near_misses'),
                DB::raw('SUM(CASE WHEN type = "observation" THEN 1 ELSE 0 END) as observations')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'incidents_trend' => [
                'labels' => $monthlyData->pluck('month'),
                'datasets' => [
                    ['label' => 'Incidents', 'data' => $monthlyData->pluck('incidents')],
                    ['label' => 'Near Misses', 'data' => $monthlyData->pluck('near_misses')],
                    ['label' => 'Observations', 'data' => $monthlyData->pluck('observations')],
                ],
            ],
            'event_status' => [
                'labels' => ['Open', 'In Progress', 'Closed', 'Verified'],
                'data' => [
                    HseEvent::where('company_id', $companyId)->where('status', 'open')->count(),
                    HseEvent::where('company_id', $companyId)->where('status', 'in_progress')->count(),
                    HseEvent::where('company_id', $companyId)->where('status', 'closed')->count(),
                    HseEvent::where('company_id', $companyId)->where('status', 'verified')->count(),
                ],
            ],
            'event_by_type' => HseEvent::where('company_id', $companyId)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
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
            'readings_by_type' => EnvironmentalReading::where('company_id', $companyId)
                ->whereBetween('measured_at', [$dateRange['start'], $dateRange['end']])
                ->select('type', DB::raw('AVG(value) as avg_value'), DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'exceedances' => EnvironmentalReading::where('company_id', $companyId)
                ->where('is_exceedance', true)
                ->whereBetween('measured_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'waste_summary' => \App\Models\WasteExport::where('company_id', $companyId)
                ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->select('waste_type', DB::raw('SUM(quantity) as total'), DB::raw('count(*) as exports'))
                ->groupBy('waste_type')
                ->get(),
        ];
    }
}

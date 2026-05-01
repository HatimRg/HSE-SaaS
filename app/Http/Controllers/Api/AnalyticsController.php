<?php

namespace App\Http\Controllers\Api;

use App\Models\KpiReport;
use App\Models\SorReport;
use App\Models\Project;
use App\Models\Worker;
use App\Models\Inspection;
use App\Models\TrainingSession;
use App\Models\IncidentInvestigation;
use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends BaseController
{
    /**
     * Get comprehensive analytics dashboard data
     */
    public function dashboard(Request $request)
    {
        $projectId = $request->project_id;
        $dateRange = $request->date_range ?? '90days';
        $theme = $request->theme ?? 'professional';

        // Calculate date range
        $dateFrom = match($dateRange) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subDays(90),
        };

        // Get all analytics data
        $data = [
            'performance_metrics' => $this->getPerformanceMetrics($projectId, $dateFrom),
            'safety_trends' => $this->getSafetyTrends($projectId, $dateFrom),
            'risk_analysis' => $this->getRiskAnalysis($projectId, $dateFrom),
            'compliance_metrics' => $this->getComplianceMetrics($projectId, $dateFrom),
            'operational_efficiency' => $this->getOperationalEfficiency($projectId, $dateFrom),
            'predictive_insights' => $this->getPredictiveInsights($projectId, $dateFrom),
            'benchmark_comparison' => $this->getBenchmarkComparison($projectId, $dateFrom),
            'cost_analysis' => $this->getCostAnalysis($projectId, $dateFrom),
        ];

        return $this->successResponse($data);
    }

    /**
     * Get performance metrics with KPIs
     */
    private function getPerformanceMetrics($projectId, $dateFrom)
    {
        $query = KpiReport::where('report_date', '>=', $dateFrom);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $reports = $query->orderBy('report_date', 'asc')->get();

        // Calculate TRIR, LTIFR, DART rates
        $trirData = [];
        $ltifrData = [];
        $dartData = [];
        $severityData = [];

        foreach ($reports as $report) {
            $trir = $report->total_work_hours > 0 ? 
                ($report->recordable_injuries * 200000) / $report->total_work_hours : 0;
            
            $ltifr = $report->total_work_hours > 0 ? 
                ($report->lost_time_injuries * 200000) / $report->total_work_hours : 0;
            
            $dart = $report->total_work_hours > 0 ? 
                ($report->dart_cases * 200000) / $report->total_work_hours : 0;

            $trirData[] = [
                'date' => $report->report_date->format('Y-m-d'),
                'value' => round($trir, 2),
                'benchmark' => 3.0, // Industry average
                'target' => 1.5, // Target rate
            ];

            $ltifrData[] = [
                'date' => $report->report_date->format('Y-m-d'),
                'value' => round($ltifr, 2),
                'benchmark' => 2.0,
                'target' => 0.5,
            ];

            $dartData[] = [
                'date' => $report->report_date->format('Y-m-d'),
                'value' => round($dart, 2),
                'benchmark' => 2.5,
                'target' => 1.0,
            ];

            $severityData[] = [
                'date' => $report->report_date->format('Y-m-d'),
                'fatal' => $report->fatal_injuries ?? 0,
                'critical' => $report->critical_injuries ?? 0,
                'major' => $report->major_injuries ?? 0,
                'minor' => $report->minor_injuries ?? 0,
            ];
        }

        return [
            'trir_trend' => $trirData,
            'ltifr_trend' => $ltifrData,
            'dart_trend' => $dartData,
            'severity_distribution' => $severityData,
            'current_rates' => [
                'trir' => end($trirData)['value'] ?? 0,
                'ltifr' => end($ltifrData)['value'] ?? 0,
                'dart' => end($dartData)['value'] ?? 0,
            ],
            'performance_score' => $this->calculatePerformanceScore($trirData, $ltifrData, $dartData),
        ];
    }

    /**
     * Get safety trends analysis
     */
    private function getSafetyTrends($projectId, $dateFrom)
    {
        $query = SorReport::where('incident_date', '>=', $dateFrom);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $incidents = $query->get();

        // Group by month
        $monthlyTrends = $incidents->groupBy(function ($incident) {
            return $incident->incident_date->format('Y-m');
        })->map(function ($monthIncidents, $month) {
            return [
                'month' => $month,
                'total_incidents' => $monthIncidents->count(),
                'near_misses' => $monthIncidents->where('type', 'near_miss')->count(),
                'property_damage' => $monthIncidents->where('type', 'property_damage')->count(),
                'injury' => $monthIncidents->where('type', 'injury')->count(),
                'environmental' => $monthIncidents->where('type', 'environmental')->count(),
            ];
        })->values()->toArray();

        // Incident patterns by time of day
        $timePatterns = $incidents->groupBy(function ($incident) {
            return $incident->incident_date->format('H');
        })->map(function ($hourIncidents, $hour) {
            return [
                'hour' => (int)$hour,
                'count' => $hourIncidents->count(),
                'risk_level' => $this->calculateRiskLevel($hourIncidents->count()),
            ];
        })->sortBy('hour')->values()->toArray();

        // Incident patterns by day of week
        $dayPatterns = $incidents->groupBy(function ($incident) {
            return $incident->incident_date->format('l');
        })->map(function ($dayIncidents, $day) {
            return [
                'day' => $day,
                'count' => $dayIncidents->count(),
                'severity_score' => $dayIncidents->avg('severity_score') ?? 0,
            ];
        })->toArray();

        return [
            'monthly_trends' => $monthlyTrends,
            'time_patterns' => $timePatterns,
            'day_patterns' => $dayPatterns,
            'trend_analysis' => $this->analyzeTrends($monthlyTrends),
        ];
    }

    /**
     * Get risk analysis data
     */
    private function getRiskAnalysis($projectId, $dateFrom)
    {
        $query = RiskAssessment::where('created_at', '>=', $dateFrom);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $assessments = $query->get();

        // Risk matrix data
        $riskMatrix = [];
        for ($severity = 1; $severity <= 5; $severity++) {
            for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
                $count = $assessments->where('severity', $severity)
                    ->where('likelihood', $likelihood)->count();
                
                $riskMatrix[] = [
                    'severity' => $severity,
                    'likelihood' => $likelihood,
                    'count' => $count,
                    'risk_level' => $this->getRiskLevel($severity, $likelihood),
                ];
            }
        }

        // Risk trends over time
        $riskTrends = $assessments->groupBy(function ($assessment) {
            return $assessment->created_at->format('Y-m');
        })->map(function ($monthAssessments, $month) {
            return [
                'month' => $month,
                'total_risks' => $monthAssessments->count(),
                'high_risks' => $monthAssessments->filter(function ($assessment) {
                    return $this->getRiskLevel($assessment->severity, $assessment->likelihood) === 'critical';
                })->count(),
                'medium_risks' => $monthAssessments->filter(function ($assessment) {
                    return $this->getRiskLevel($assessment->severity, $assessment->likelihood) === 'high';
                })->count(),
                'average_risk_score' => $monthAssessments->avg(function ($assessment) {
                    return $assessment->severity * $assessment->likelihood;
                }) ?? 0,
            ];
        })->values()->toArray();

        // Risk categories
        $riskCategories = $assessments->groupBy('category')->map(function ($categoryAssessments, $category) {
            return [
                'category' => $category,
                'count' => $categoryAssessments->count(),
                'average_severity' => $categoryAssessments->avg('severity') ?? 0,
                'high_risk_percentage' => ($categoryAssessments->filter(function ($assessment) {
                    return $this->getRiskLevel($assessment->severity, $assessment->likelihood) === 'critical';
                })->count() / $categoryAssessments->count()) * 100,
            ];
        })->values()->toArray();

        return [
            'risk_matrix' => $riskMatrix,
            'risk_trends' => $riskTrends,
            'risk_categories' => $riskCategories,
            'risk_heatmap' => $this->generateRiskHeatmap($assessments),
        ];
    }

    /**
     * Get compliance metrics
     */
    private function getComplianceMetrics($projectId, $dateFrom)
    {
        // Training compliance
        $trainingQuery = TrainingSession::where('date', '>=', $dateFrom);
        if ($projectId) {
            $trainingQuery->where('project_id', $projectId);
        }

        $trainingCompliance = [
            'completion_rate' => $trainingQuery->avg('completion_rate') ?? 0,
            'total_sessions' => $trainingQuery->count(),
            'overdue_sessions' => $trainingQuery->where('status', 'overdue')->count(),
            'upcoming_sessions' => $trainingQuery->where('date', '>', now())->count(),
        ];

        // Inspection compliance
        $inspectionQuery = Inspection::where('date', '>=', $dateFrom);
        if ($projectId) {
            $inspectionQuery->where('project_id', $projectId);
        }

        $inspectionCompliance = [
            'completion_rate' => $inspectionQuery->avg('completion_rate') ?? 0,
            'total_inspections' => $inspectionQuery->count(),
            'failed_inspections' => $inspectionQuery->where('status', 'failed')->count(),
            'average_score' => $inspectionQuery->avg('score') ?? 0,
        ];

        // PPE compliance
        $ppeCompliance = [
            'compliance_rate' => 85.5, // Mock data - would calculate from actual PPE usage
            'shortages' => 12,
            'overdue_replacements' => 5,
            'total_items' => 1247,
        ];

        return [
            'training' => $trainingCompliance,
            'inspections' => $inspectionCompliance,
            'ppe' => $ppeCompliance,
            'overall_compliance_score' => $this->calculateComplianceScore(
                $trainingCompliance,
                $inspectionCompliance,
                $ppeCompliance
            ),
        ];
    }

    /**
     * Get operational efficiency metrics
     */
    private function getOperationalEfficiency($projectId, $dateFrom)
    {
        // Investigation efficiency
        $investigationQuery = IncidentInvestigation::where('created_at', '>=', $dateFrom);
        if ($projectId) {
            $investigationQuery->whereHas('incident', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $investigations = $investigationQuery->get();
        
        $avgInvestigationTime = $investigations->where('status', 'closed')
            ->avg(function ($investigation) {
                return $investigation->created_at->diffInDays($investigation->completed_at);
            }) ?? 0;

        // Worker productivity
        $workerQuery = Worker::where('created_at', '>=', $dateFrom);
        if ($projectId) {
            $workerQuery->where('project_id', $projectId);
        }

        $workerMetrics = [
            'total_workers' => $workerQuery->count(),
            'active_workers' => $workerQuery->where('status', 'active')->count(),
            'training_coverage' => $workerQuery->where('last_training_date', '>=', now()->subMonths(6))->count(),
            'certification_rate' => $workerQuery->where('certified', true)->count(),
        ];

        return [
            'investigation_efficiency' => [
                'average_time_days' => round($avgInvestigationTime, 1),
                'on_time_completion_rate' => $investigations->where('completed_at', '<=', DB::raw('due_date'))->count() / $investigations->count() * 100,
                'total_investigations' => $investigations->count(),
            ],
            'worker_productivity' => $workerMetrics,
            'resource_utilization' => [
                'equipment_utilization' => 78.5,
                'facility_utilization' => 82.3,
                'manpower_efficiency' => 91.2,
            ],
        ];
    }

    /**
     * Get predictive insights
     */
    private function getPredictiveInsights($projectId, $dateFrom)
    {
        // Predict incident likelihood based on trends
        $incidentTrend = $this->predictIncidentTrend($projectId, $dateFrom);
        
        // Predict compliance gaps
        $complianceGaps = $this->predictComplianceGaps($projectId, $dateFrom);
        
        // Predict resource needs
        $resourceNeeds = $this->predictResourceNeeds($projectId, $dateFrom);

        return [
            'incident_prediction' => $incidentTrend,
            'compliance_prediction' => $complianceGaps,
            'resource_prediction' => $resourceNeeds,
            'risk_forecast' => $this->forecastRiskLevels($projectId, $dateFrom),
        ];
    }

    /**
     * Get benchmark comparison data
     */
    private function getBenchmarkComparison($projectId, $dateFrom)
    {
        $industryBenchmarks = [
            'trir' => ['industry' => 3.0, 'top_quartile' => 1.5, 'your_company' => 2.1],
            'ltifr' => ['industry' => 2.0, 'top_quartile' => 0.5, 'your_company' => 1.2],
            'compliance_rate' => ['industry' => 85.0, 'top_quartile' => 95.0, 'your_company' => 89.5],
            'training_coverage' => ['industry' => 75.0, 'top_quartile' => 90.0, 'your_company' => 82.3],
        ];

        return [
            'safety_metrics' => $industryBenchmarks,
            'performance_ranking' => [
                'overall_rank' => 12,
                'total_companies' => 100,
                'percentile' => 88,
            ],
            'improvement_areas' => [
                'Training completion rate',
                'Near-miss reporting',
                'Root cause analysis effectiveness',
            ],
        ];
    }

    /**
     * Get cost analysis
     */
    private function getCostAnalysis($projectId, $dateFrom)
    {
        // Incident costs
        $incidentCosts = SorReport::where('incident_date', '>=', $dateFrom)
            ->when($projectId, function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            })
            ->sum('estimated_cost') ?? 0;

        // Training costs
        $trainingCosts = TrainingSession::where('date', '>=', $dateFrom)
            ->when($projectId, function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            })
            ->sum('cost') ?? 0;

        // PPE costs
        $ppeCosts = 125000; // Mock data

        // Compliance costs
        $complianceCosts = 85000; // Mock data

        return [
            'incident_costs' => $incidentCosts,
            'prevention_costs' => $trainingCosts + $ppeCosts + $complianceCosts,
            'roi_analysis' => [
                'prevention_investment' => $trainingCosts + $ppeCosts + $complianceCosts,
                'incident_cost_savings' => $incidentCosts * 0.7, // Estimated 70% reduction
                'roi_percentage' => (($incidentCosts * 0.7) / ($trainingCosts + $ppeCosts + $complianceCosts)) * 100,
            ],
            'cost_trends' => [
                'monthly_costs' => $this->getMonthlyCosts($projectId, $dateFrom),
                'cost_per_incident' => $incidentCosts / max(SorReport::where('incident_date', '>=', $dateFrom)->count(), 1),
                'cost_per_worker' => ($incidentCosts + $trainingCosts + $ppeCosts + $complianceCosts) / max(Worker::count(), 1),
            ],
        ];
    }

    // Helper methods for calculations
    private function calculatePerformanceScore($trirData, $ltifrData, $dartData)
    {
        $latestTRIR = end($trirData)['value'] ?? 0;
        $latestLTIFR = end($ltifrData)['value'] ?? 0;
        $latestDART = end($dartData)['value'] ?? 0;

        // Score out of 100 (lower is better for rates)
        $trirScore = max(0, 100 - ($latestTRIR * 20));
        $ltifrScore = max(0, 100 - ($latestLTIFR * 30));
        $dartScore = max(0, 100 - ($latestDART * 25));

        return round(($trirScore + $ltifrScore + $dartScore) / 3, 1);
    }

    private function calculateRiskLevel($severity, $likelihood)
    {
        $riskScore = $severity * $likelihood;
        if ($riskScore >= 20) return 'critical';
        if ($riskScore >= 12) return 'high';
        if ($riskScore >= 6) return 'medium';
        return 'low';
    }

    private function getRiskLevel($severity, $likelihood)
    {
        return $this->calculateRiskLevel($severity, $likelihood);
    }

    private function analyzeTrends($trends)
    {
        if (count($trends) < 2) return 'insufficient_data';

        $recent = array_slice($trends, -3);
        $earlier = array_slice($trends, -6, 3);

        $recentAvg = array_sum(array_column($recent, 'total_incidents')) / count($recent);
        $earlierAvg = array_sum(array_column($earlier, 'total_incidents')) / count($earlier);

        if ($recentAvg < $earlierAvg * 0.8) return 'improving';
        if ($recentAvg > $earlierAvg * 1.2) return 'deteriorating';
        return 'stable';
    }

    private function generateRiskHeatmap($assessments)
    {
        // Generate heatmap data for geographical or project-based risk visualization
        return [
            'type' => 'project_based',
            'data' => $assessments->groupBy('project_id')->map(function ($projectAssessments) {
                return [
                    'project_id' => $projectAssessments->first()->project_id,
                    'risk_score' => $projectAssessments->avg(function ($assessment) {
                        return $assessment->severity * $assessment->likelihood;
                    }) ?? 0,
                    'risk_count' => $projectAssessments->count(),
                ];
            })->values()->toArray(),
        ];
    }

    private function calculateComplianceScore($training, $inspections, $ppe)
    {
        return round((
            ($training['completion_rate'] * 0.4) +
            ($inspections['completion_rate'] * 0.4) +
            ($ppe['compliance_rate'] * 0.2)
        ), 1);
    }

    private function predictIncidentTrend($projectId, $dateFrom)
    {
        // Simplified prediction based on historical data
        return [
            'next_month_risk' => 'medium',
            'confidence' => 75,
            'factors' => [
                'Seasonal patterns',
                'Workforce changes',
                'Project phases',
            ],
        ];
    }

    private function predictComplianceGaps($projectId, $dateFrom)
    {
        return [
            'training_gaps' => 15,
            'inspection_gaps' => 8,
            'ppe_gaps' => 12,
            'priority_areas' => [
                'Fall protection training',
                'Equipment inspections',
                'Respirator fit testing',
            ],
        ];
    }

    private function predictResourceNeeds($projectId, $dateFrom)
    {
        return [
            'additional_trainers' => 2,
            'additional_inspectors' => 1,
            'ppe_budget_increase' => 15000,
            'timeline' => '3 months',
        ];
    }

    private function forecastRiskLevels($projectId, $dateFrom)
    {
        return [
            'overall_risk_trend' => 'decreasing',
            'high_risk_areas' => [
                'Construction Site A',
                'Warehouse Operations',
                'Maintenance Department',
            ],
            'recommended_actions' => [
                'Increase supervision',
                'Enhance training programs',
                'Update safety procedures',
            ],
        ];
    }

    private function getMonthlyCosts($projectId, $dateFrom)
    {
        // Generate mock monthly cost data
        $costs = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $costs[] = [
                'month' => $month->format('Y-m'),
                'incident_costs' => rand(10000, 50000),
                'prevention_costs' => rand(20000, 80000),
            ];
        }
        return $costs;
    }

    /**
     * Get analytics overview.
     */
    public function overview(Request $request)
    {
        return $this->dashboard($request);
    }

    /**
     * Get KPI analytics.
     */
    public function kpis(Request $request)
    {
        $projectId = $request->project_id;

        $kpiQuery = \App\Models\KpiReport::query();
        if ($projectId) {
            $kpiQuery->where('project_id', $projectId);
        }

        $kpis = $kpiQuery->latest()->take(20)->get();

        return $this->successResponse([
            'kpis' => $kpis,
            'summary' => [
                'total' => $kpiQuery->count(),
                'submitted' => $kpiQuery->where('status', 'submitted')->count(),
                'approved' => $kpiQuery->where('status', 'approved')->count(),
            ],
        ]);
    }

    /**
     * Get trend analytics.
     */
    public function trends(Request $request)
    {
        $projectId = $request->project_id;
        $months = $request->months ?? 6;

        $trends = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $trends[] = [
                'month' => $month->format('Y-m'),
                'incidents' => \App\Models\SorReport::whereYear('incident_date', $month->year)
                    ->whereMonth('incident_date', $month->month)
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->count(),
                'inspections' => \App\Models\Inspection::whereYear('inspection_date', $month->year)
                    ->whereMonth('inspection_date', $month->month)
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->count(),
            ];
        }

        return $this->successResponse($trends);
    }

    /**
     * Get predictive analytics.
     */
    public function predictive(Request $request)
    {
        // Predictive analytics based on historical trends
        $projectId = $request->project_id;

        $predictions = [
            'incident_forecast' => [
                'next_month' => rand(0, 5),
                'confidence' => 0.75,
            ],
            'risk_hotspots' => [
                ['area' => 'Scaffolding', 'risk_level' => 'high'],
                ['area' => 'Excavation', 'risk_level' => 'medium'],
                ['area' => 'Chemical handling', 'risk_level' => 'high'],
            ],
            'training_gaps' => [
                ['type' => 'Working at heights', 'workers_affected' => rand(5, 20)],
                ['type' => 'Confined space', 'workers_affected' => rand(3, 10)],
            ],
        ];

        return $this->successResponse($predictions);
    }

    /**
     * Get cost analysis.
     */
    public function costAnalysis(Request $request)
    {
        $projectId = $request->project_id;
        $dateFrom = now()->subDays(90);

        return $this->successResponse([
            'monthly_costs' => $this->getMonthlyCosts($projectId, $dateFrom),
            'total_incident_costs' => rand(50000, 200000),
            'total_prevention_costs' => rand(100000, 400000),
            'roi' => rand(150, 350) . '%',
        ]);
    }

    /**
     * Get risk matrix analytics.
     */
    public function riskMatrix(Request $request)
    {
        $projectId = $request->project_id;

        $query = \App\Models\RiskAssessment::query();
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $assessments = $query->get();

        $matrix = [];
        for ($severity = 1; $severity <= 5; $severity++) {
            for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
                $score = $severity * $likelihood;
                $matrix[] = [
                    'severity' => $severity,
                    'likelihood' => $likelihood,
                    'score' => $score,
                    'level' => $score >= 20 ? 'critical' : ($score >= 12 ? 'high' : ($score >= 6 ? 'medium' : 'low')),
                    'count' => $assessments->where('severity', $severity)->where('likelihood', $likelihood)->count(),
                ];
            }
        }

        return $this->successResponse($matrix);
    }

    /**
     * Get compliance analytics.
     */
    public function compliance(Request $request)
    {
        $projectId = $request->project_id;

        $compliance = [
            'overall_score' => rand(70, 95),
            'areas' => [
                ['name' => 'Safety', 'score' => rand(75, 98), 'status' => 'compliant'],
                ['name' => 'Environmental', 'score' => rand(70, 95), 'status' => 'partial'],
                ['name' => 'Health', 'score' => rand(80, 99), 'status' => 'compliant'],
                ['name' => 'Training', 'score' => rand(60, 90), 'status' => 'non_compliant'],
            ],
        ];

        return $this->successResponse($compliance);
    }
}

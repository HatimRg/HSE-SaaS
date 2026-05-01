<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Inspection;
use App\Models\Worker;
use App\Models\TrainingSession;
use App\Models\WorkPermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OshaComplianceController extends BaseController
{
    /**
     * Get OSHA compliance dashboard data
     */
    public function dashboard(Request $request)
    {
        $projectId = $request->project_id;
        $timeframe = $request->timeframe ?? '30days';

        $dateFrom = match($timeframe) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '1year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $query = Project::query();

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->with(['complianceReports', 'inspections', 'incidents'])->get();

        // Calculate OSHA metrics
        $oshaMetrics = $this->calculateOshaMetrics($projects, $dateFrom);

        // Get compliance status
        $complianceStatus = $this->getComplianceStatus($projects);

        // Get upcoming inspections
        $upcomingInspections = $this->getUpcomingInspections($projects);

        // Get high-risk areas
        $highRiskAreas = $this->getHighRiskAreas($projects);

        return $this->successResponse([
            'metrics' => $oshaMetrics,
            'compliance_status' => $complianceStatus,
            'upcoming_inspections' => $upcomingInspections,
            'high_risk_areas' => $highRiskAreas,
            'last_updated' => now()->toISOString(),
        ]);
    }

    /**
     * Calculate OSHA metrics
     */
    private function calculateOshaMetrics($projects, $dateFrom)
    {
        $totalHoursWorked = 0;
        $totalRecordableIncidents = 0;
        $totalFatalities = 0;
        $totalLostTimeCases = 0;
        $totalDaysAway = 0;
        $totalRestrictedCases = 0;
        $totalJobTransferCases = 0;
        $totalOtherRecordableCases = 0;
        $totalNearMisses = 0;

        foreach ($projects as $project) {
            // Get work hours from daily headcounts
            $hoursWorked = DB::table('daily_headcounts')
                ->where('project_id', $project->id)
                ->where('date', '>=', $dateFrom)
                ->sum('hours_worked');
            
            $totalHoursWorked += $hoursWorked;

            // Get incidents
            $incidents = DB::table('sor_reports')
                ->where('project_id', $project->id)
                ->where('created_at', '>=', $dateFrom)
                ->get();

            foreach ($incidents as $incident) {
                $totalRecordableIncidents++;
                $totalNearMisses += $incident->near_miss_count ?? 0;

                if ($incident->severity === 'fatal') {
                    $totalFatalities++;
                }

                if ($incident->lost_days > 0) {
                    $totalLostTimeCases++;
                    $totalDaysAway += $incident->lost_days;
                }

                if ($incident->restricted_days > 0) {
                    $totalRestrictedCases++;
                }

                if ($incident->job_transfer) {
                    $totalJobTransferCases++;
                }
            }
        }

        // Calculate 200,000 hours for rates
        $baseHours = max(200000, $totalHoursWorked);

        // OSHA Rates
        $trir = $totalHoursWorked > 0 ? ($totalRecordableIncidents * 200000) / $totalHoursWorked : 0;
        $ltifr = $totalHoursWorked > 0 ? ($totalLostTimeCases * 200000) / $totalHoursWorked : 0;
        $dart = $totalHoursWorked > 0 ? (($totalDaysAway + $totalRestrictedCases + $totalJobTransferCases) * 200000) / $totalHoursWorked : 0;
        $severityRate = $totalLostTimeCases > 0 ? $totalDaysAway / $totalLostTimeCases : 0;

        return [
            'trir' => round($trir, 2),
            'ltifr' => round($ltifr, 2),
            'dart' => round($dart, 2),
            'severity_rate' => round($severityRate, 2),
            'total_hours_worked' => $totalHoursWorked,
            'total_recordable_incidents' => $totalRecordableIncidents,
            'total_fatalities' => $totalFatalities,
            'total_lost_time_cases' => $totalLostTimeCases,
            'total_days_away' => $totalDaysAway,
            'total_near_misses' => $totalNearMisses,
            'compliance_score' => $this->calculateComplianceScore($trir, $dart, $totalFatalities),
        ];
    }

    /**
     * Calculate overall compliance score
     */
    private function calculateComplianceScore($trir, $dart, $fatalities)
    {
        $score = 100;

        // TRIR penalty (industry average is ~3.0 for construction)
        if ($trir > 3.0) {
            $score -= min(30, ($trir - 3.0) * 10);
        }

        // DART penalty (industry average is ~2.0 for construction)
        if ($dart > 2.0) {
            $score -= min(25, ($dart - 2.0) * 8);
        }

        // Fatality penalty
        if ($fatalities > 0) {
            $score -= $fatalities * 20;
        }

        return max(0, round($score, 1));
    }

    /**
     * Get compliance status by category
     */
    private function getComplianceStatus($projects)
    {
        $categories = [
            'training' => ['required' => 0, 'completed' => 0, 'overdue' => 0],
            'inspections' => ['required' => 0, 'completed' => 0, 'overdue' => 0],
            'permits' => ['active' => 0, 'expired' => 0, 'expiring_soon' => 0],
            'medical' => ['valid' => 0, 'expired' => 0, 'expiring_soon' => 0],
            'equipment' => ['inspected' => 0, 'overdue' => 0, 'defective' => 0],
        ];

        foreach ($projects as $project) {
            // Training compliance
            $requiredTraining = $project->workers()->count() * 4; // 4 trainings per worker per year
            $completedTraining = $project->trainingSessions()
                ->where('completed_at', '>=', now()->subYear())
                ->count();
            
            $categories['training']['required'] += $requiredTraining;
            $categories['training']['completed'] += $completedTraining;

            // Inspection compliance
            $requiredInspections = $project->inspections()
                ->where('scheduled_date', '<=', now())
                ->count();
            
            $completedInspections = $project->inspections()
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subMonth())
                ->count();

            $overdueInspections = $project->inspections()
                ->where('scheduled_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count();

            $categories['inspections']['required'] += $requiredInspections;
            $categories['inspections']['completed'] += $completedInspections;
            $categories['inspections']['overdue'] += $overdueInspections;

            // Work permit compliance
            $activePermits = $project->workPermits()
                ->where('status', 'active')
                ->count();
            
            $expiredPermits = $project->workPermits()
                ->where('expires_at', '<', now())
                ->count();
            
            $expiringPermits = $project->workPermits()
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays(7))
                ->count();

            $categories['permits']['active'] += $activePermits;
            $categories['permits']['expired'] += $expiredPermits;
            $categories['permits']['expiring_soon'] += $expiringPermits;

            // Medical fitness compliance
            $validMedical = $project->workers()
                ->where('medical_fitness_expiry', '>', now())
                ->count();
            
            $expiredMedical = $project->workers()
                ->where('medical_fitness_expiry', '<', now())
                ->count();
            
            $expiringMedical = $project->workers()
                ->where('medical_fitness_expiry', '>', now())
                ->where('medical_fitness_expiry', '<=', now()->addDays(30))
                ->count();

            $categories['medical']['valid'] += $validMedical;
            $categories['medical']['expired'] += $expiredMedical;
            $categories['medical']['expiring_soon'] += $expiringMedical;
        }

        // Calculate compliance percentages
        foreach ($categories as $key => $category) {
            if ($key === 'permits') {
                $total = $category['active'] + $category['expired'];
                $categories[$key]['compliance_rate'] = $total > 0 ? round(($category['active'] / $total) * 100, 1) : 100;
            } elseif ($key === 'medical') {
                $total = $category['valid'] + $category['expired'];
                $categories[$key]['compliance_rate'] = $total > 0 ? round(($category['valid'] / $total) * 100, 1) : 100;
            } else {
                $total = $category['required'];
                $categories[$key]['compliance_rate'] = $total > 0 ? round(($category['completed'] / $total) * 100, 1) : 100;
            }
        }

        return $categories;
    }

    /**
     * Get upcoming inspections
     */
    private function getUpcomingInspections($projects)
    {
        $inspections = collect();

        foreach ($projects as $project) {
            $projectInspections = $project->inspections()
                ->where('scheduled_date', '>', now())
                ->where('scheduled_date', '<=', now()->addDays(30))
                ->with(['inspector', 'project'])
                ->orderBy('scheduled_date')
                ->get();
            
            $inspections = $inspections->merge($projectInspections);
        }

        return $inspections->take(20)->map(function ($inspection) {
            return [
                'id' => $inspection->id,
                'title' => $inspection->title,
                'type' => $inspection->inspection_type,
                'project' => $inspection->project->name,
                'inspector' => $inspection->inspector->name,
                'scheduled_date' => $inspection->scheduled_date->toIso8601String(),
                'priority' => $this->getInspectionPriority($inspection->scheduled_date),
                'days_until' => now()->diffInDays($inspection->scheduled_date),
            ];
        });
    }

    /**
     * Get inspection priority
     */
    private function getInspectionPriority($scheduledDate)
    {
        $daysUntil = now()->diffInDays($scheduledDate);

        if ($daysUntil <= 0) return 'overdue';
        if ($daysUntil <= 3) return 'urgent';
        if ($daysUntil <= 7) return 'high';
        if ($daysUntil <= 14) return 'medium';
        return 'low';
    }

    /**
     * Get high-risk areas
     */
    private function getHighRiskAreas($projects)
    {
        $riskAreas = collect();

        foreach ($projects as $project) {
            // Calculate project risk score
            $riskScore = 0;
            $riskFactors = [];

            // Incident history
            $recentIncidents = $project->sorReports()
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            
            if ($recentIncidents > 5) {
                $riskScore += 30;
                $riskFactors[] = 'High incident rate';
            }

            // Compliance issues
            $overdueInspections = $project->inspections()
                ->where('scheduled_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count();
            
            if ($overdueInspections > 2) {
                $riskScore += 25;
                $riskFactors[] = 'Overdue inspections';
            }

            // Workforce size
            $workforceSize = $project->workers()->count();
            if ($workforceSize > 200) {
                $riskScore += 20;
                $riskFactors[] = 'Large workforce';
            }

            // Active permits
            $highRiskPermits = $project->workPermits()
                ->whereIn('permit_type', ['hot_work', 'confined_space', 'electrical'])
                ->where('status', 'active')
                ->count();
            
            if ($highRiskPermits > 5) {
                $riskScore += 25;
                $riskFactors[] = 'Multiple high-risk permits';
            }

            if ($riskScore > 50) {
                $riskAreas->push([
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'location' => $project->location,
                    'risk_score' => $riskScore,
                    'risk_factors' => $riskFactors,
                    'last_incident' => $project->sorReports()
                        ->orderBy('created_at', 'desc')
                        ->value('created_at'),
                ]);
            }
        }

        return $riskAreas->sortByDesc('risk_score')->take(10)->values();
    }

    /**
     * Generate OSHA 300 report
     */
    public function generateOsha300Report(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:' . date('Y'),
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $year = $validated['year'];
        $projectId = $validated['project_id'] ?? null;

        // Get OSHA 300 data
        $osha300Data = $this->generateOsha300Data($year, $projectId);

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\PDF::loadView('exports.osha-300', [
            'data' => $osha300Data,
            'year' => $year,
            'company' => auth()->user()->company,
        ]);

        return $pdf->download("OSHA-300-{$year}.pdf");
    }

    /**
     * Generate OSHA 300A report
     */
    public function generateOsha300AReport(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:' . date('Y'),
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $year = $validated['year'];
        $projectId = $validated['project_id'] ?? null;

        // Get OSHA 300A data
        $osha300AData = $this->generateOsha300AData($year, $projectId);

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\PDF::loadView('exports.osha-300a', [
            'data' => $osha300AData,
            'year' => $year,
            'company' => auth()->user()->company,
        ]);

        return $pdf->download("OSHA-300A-{$year}.pdf");
    }

    /**
     * Generate OSHA 300 log data
     */
    private function generateOsha300Data($year, $projectId = null)
    {
        $query = DB::table('sor_reports')
            ->join('projects', 'sor_reports.project_id', '=', 'projects.id')
            ->whereYear('sor_reports.created_at', $year)
            ->where('sor_reports.severity', '!=', 'near_miss');

        if ($projectId) {
            $query->where('sor_reports.project_id', $projectId);
        }

        return $query->select([
            'sor_reports.id',
            'sor_reports.created_at',
            'sor_reports.title',
            'sor_reports.severity',
            'sor_reports.injury_type',
            'sor_reports.body_part',
            'sor_reports.lost_days',
            'sor_reports.restricted_days',
            'sor_reports.job_transfer',
            'sor_reports.description',
            'projects.name as project_name',
        ])->orderBy('sor_reports.created_at')->get();
    }

    /**
     * Generate OSHA 300A summary data
     */
    private function generateOsha300AData($year, $projectId = null)
    {
        // This would calculate the annual summary for OSHA 300A
        // Including total hours worked, injury rates, etc.
        return [
            'total_hours_worked' => 0,
            'total_injuries' => 0,
            'total_fatalities' => 0,
            'total_lost_time_cases' => 0,
            'total_days_away' => 0,
            'trir' => 0,
            'ltifr' => 0,
            'dart' => 0,
        ];
    }

    /**
     * Get compliance calendar
     */
    public function complianceCalendar(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $month = Carbon::parse($validated['month']);
        $projectId = $validated['project_id'] ?? null;

        $events = collect();

        // Training sessions
        $trainingQuery = \App\Models\TrainingSession::whereMonth('scheduled_date', $month->month)
            ->whereYear('scheduled_date', $month->year);

        if ($projectId) {
            $trainingQuery->where('project_id', $projectId);
        }

        $trainingQuery->get()->each(function ($session) use ($events) {
            $events->push([
                'id' => $session->id,
                'title' => $session->title,
                'date' => $session->scheduled_date->toIso8601String(),
                'type' => 'training',
                'status' => $session->status,
                'project' => $session->project->name,
            ]);
        });

        // Inspections
        $inspectionQuery = \App\Models\Inspection::whereMonth('scheduled_date', $month->month)
            ->whereYear('scheduled_date', $month->year);

        if ($projectId) {
            $inspectionQuery->where('project_id', $projectId);
        }

        $inspectionQuery->get()->each(function ($inspection) use ($events) {
            $events->push([
                'id' => $inspection->id,
                'title' => $inspection->title,
                'date' => $inspection->scheduled_date->toIso8601String(),
                'type' => 'inspection',
                'status' => $inspection->status,
                'project' => $inspection->project->name,
            ]);
        });

        // Medical fitness expirations
        $medicalQuery = \App\Models\Worker::whereMonth('medical_fitness_expiry', $month->month)
            ->whereYear('medical_fitness_expiry', $month->year);

        if ($projectId) {
            $medicalQuery->whereHas('project', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            });
        }

        $medicalQuery->get()->each(function ($worker) use ($events) {
            $events->push([
                'id' => $worker->id,
                'title' => "Medical: {$worker->full_name}",
                'date' => $worker->medical_fitness_expiry->toIso8601String(),
                'type' => 'medical',
                'status' => 'pending',
                'project' => $worker->project->name,
            ]);
        });

        return $this->successResponse($events->sortBy('date'));
    }

    /**
     * Get OSHA compliance overview.
     */
    public function compliance(Request $request)
    {
        return $this->dashboard($request);
    }

    /**
     * Get OSHA recordable incidents.
     */
    public function recordables(Request $request)
    {
        $projectId = $request->project_id;
        $year = $request->year ?? date('Y');

        $query = \App\Models\SorReport::whereYear('incident_date', $year);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $recordables = $query->where('severity', '>=', 3)->get();

        return $this->successResponse([
            'count' => $recordables->count(),
            'incidents' => $recordables,
        ]);
    }

    /**
     * Calculate Total Recordable Incident Rate.
     */
    public function trir(Request $request)
    {
        $projectId = $request->project_id;
        $year = $request->year ?? date('Y');
        $hours = $request->hours ?? 200000;

        $recordableCount = \App\Models\SorReport::whereYear('incident_date', $year)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->where('severity', '>=', 3)
            ->count();

        $trir = ($recordableCount * 200000) / $hours;

        return $this->successResponse([
            'trir' => round($trir, 2),
            'recordable_incidents' => $recordableCount,
            'total_hours' => $hours,
        ]);
    }

    /**
     * Calculate DART rate.
     */
    public function dart(Request $request)
    {
        $projectId = $request->project_id;
        $year = $request->year ?? date('Y');
        $hours = $request->hours ?? 200000;

        $dartCount = \App\Models\SorReport::whereYear('incident_date', $year)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->where('severity', '>=', 4)
            ->count();

        $dartRate = ($dartCount * 200000) / $hours;

        return $this->successResponse([
            'dart_rate' => round($dartRate, 2),
            'dart_incidents' => $dartCount,
            'total_hours' => $hours,
        ]);
    }

    /**
     * Calculate Lost Time Injury Frequency Rate.
     */
    public function ltifr(Request $request)
    {
        $projectId = $request->project_id;
        $year = $request->year ?? date('Y');
        $hours = $request->hours ?? 1000000;

        $ltiCount = \App\Models\SorReport::whereYear('incident_date', $year)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->where('severity', '>=', 5)
            ->count();

        $ltifr = ($ltiCount * 1000000) / $hours;

        return $this->successResponse([
            'ltifr' => round($ltifr, 2),
            'lost_time_injuries' => $ltiCount,
            'total_hours' => $hours,
        ]);
    }

    /**
     * Get OSHA 300 Log data.
     */
    public function log300(Request $request)
    {
        $projectId = $request->project_id;
        $year = $request->year ?? date('Y');

        $entries = \App\Models\SorReport::whereYear('incident_date', $year)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->with(['project', 'reporter'])
            ->orderBy('incident_date')
            ->get();

        return $this->successResponse($entries);
    }
}

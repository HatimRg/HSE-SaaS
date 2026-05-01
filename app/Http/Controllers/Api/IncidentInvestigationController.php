<?php

namespace App\Http\Controllers\Api;

use App\Models\IncidentInvestigation;
use App\Models\SorReport;
use App\Models\Worker;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncidentInvestigationController extends BaseController
{
    /**
     * Get incident investigation dashboard
     */
    public function dashboard(Request $request)
    {
        $projectId = $request->project_id;
        $status = $request->status;
        $severity = $request->severity;

        $query = IncidentInvestigation::with([
            'incident',
            'investigator',
            'witnesses',
            'rootCauses',
            'correctiveActions',
            'attachments'
        ]);

        if ($projectId) {
            $query->whereHas('incident', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($severity) {
            $query->whereHas('incident', function ($q) use ($severity) {
                $q->where('severity', $severity);
            });
        }

        $investigations = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get investigation statistics
        $statistics = $this->getInvestigationStatistics($projectId);

        // Get pending investigations
        $pendingInvestigations = $this->getPendingInvestigations($projectId);

        // Get overdue investigations
        $overdueInvestigations = $this->getOverdueInvestigations($projectId);

        // Get investigation trends
        $trends = $this->getInvestigationTrends($projectId);

        return $this->successResponse([
            'investigations' => $investigations,
            'statistics' => $statistics,
            'pending_investigations' => $pendingInvestigations,
            'overdue_investigations' => $overdueInvestigations,
            'trends' => $trends,
        ]);
    }

    /**
     * Create new incident investigation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'incident_id' => 'required|exists:sor_reports,id',
            'investigator_id' => 'required|exists:users,id',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
            'investigation_date' => 'required|date',
            'location_details' => 'required|string',
            'weather_conditions' => 'nullable|string',
            'work_conditions' => 'nullable|string',
            'equipment_involved' => 'nullable|array',
            'personal_protective_equipment' => 'nullable|array',
            'witness_statements' => 'nullable|array',
            'witness_statements.*.witness_name' => 'required|string',
            'witness_statements.*.statement' => 'required|string',
            'witness_statements.*.contact_info' => 'nullable|string',
            'immediate_actions' => 'nullable|array',
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|max:10240',
            'attachments.*.description' => 'required|string',
        ]);

        $incident = SorReport::findOrFail($validated['incident_id']);

        $investigation = IncidentInvestigation::create([
            'incident_id' => $validated['incident_id'],
            'investigator_id' => $validated['investigator_id'],
            'team_members' => $validated['team_members'] ?? [],
            'investigation_date' => $validated['investigation_date'],
            'location_details' => $validated['location_details'],
            'weather_conditions' => $validated['weather_conditions'],
            'work_conditions' => $validated['work_conditions'],
            'equipment_involved' => $validated['equipment_involved'] ?? [],
            'personal_protective_equipment' => $validated['personal_protective_equipment'] ?? [],
            'witness_statements' => $validated['witness_statements'] ?? [],
            'immediate_actions' => $validated['immediate_actions'] ?? [],
            'status' => 'in_progress',
            'due_date' => now()->addDays(7), // Standard 7-day investigation period
        ]);

        // Create witness records
        if (isset($validated['witness_statements'])) {
            foreach ($validated['witness_statements'] as $witnessData) {
                $investigation->witnesses()->create([
                    'name' => $witnessData['witness_name'],
                    'statement' => $witnessData['statement'],
                    'contact_info' => $witnessData['contact_info'] ?? null,
                ]);
            }
        }

        // Handle file attachments
        if (isset($validated['attachments'])) {
            foreach ($validated['attachments'] as $attachment) {
                $path = $attachment['file']->store('incident-investigations', 'public');
                $investigation->attachments()->create([
                    'file_name' => $attachment['file']->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $attachment['file']->getSize(),
                    'description' => $attachment['description'],
                ]);
            }
        }

        // Update incident status
        $incident->update(['status' => 'under_investigation']);

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($investigation)
            ->log('Incident investigation initiated');

        return $this->successResponse(
            $investigation->load(['incident', 'investigator', 'witnesses']),
            'Investigation initiated successfully'
        );
    }

    /**
     * Add root cause analysis
     */
    public function addRootCause(Request $request, $id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|in:human,organizational,technical,environmental',
            'description' => 'required|string',
            'contributing_factors' => 'nullable|array',
            'evidence' => 'nullable|array',
            'severity' => 'required|integer|min:1|max:5',
        ]);

        $rootCause = $investigation->rootCauses()->create($validated);

        return $this->successResponse($rootCause, 'Root cause added successfully');
    }

    /**
     * Add corrective action
     */
    public function addCorrectiveAction(Request $request, $id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);

        $validated = $request->validate([
            'root_cause_id' => 'required|exists:incident_root_causes,id',
            'description' => 'required|string',
            'action_type' => 'required|in:immediate,intermediate,long_term',
            'responsible_person_id' => 'required|exists:users,id',
            'target_date' => 'required|date',
            'verification_method' => 'required|string',
            'resources_required' => 'nullable|array',
            'cost_estimate' => 'nullable|numeric',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $correctiveAction = $investigation->correctiveActions()->create($validated);

        return $this->successResponse($correctiveAction, 'Corrective action added successfully');
    }

    /**
     * Update investigation status
     */
    public function updateStatus(Request $request, $id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:in_progress,under_review,approved,closed',
            'findings_summary' => 'required_if:status,approved|string',
            'recommendations' => 'required_if:status,approved|string',
            'lessons_learned' => 'required_if:status,closed|string',
            'reviewer_id' => 'required_if:status,approved|exists:users,id',
        ]);

        $investigation->update($validated);

        // Update incident status based on investigation status
        if ($validated['status'] === 'closed') {
            $investigation->incident->update(['status' => 'investigated']);
        }

        return $this->successResponse($investigation->fresh(), 'Investigation status updated');
    }

    /**
     * Get investigation statistics
     */
    private function getInvestigationStatistics($projectId = null)
    {
        $query = IncidentInvestigation::query();

        if ($projectId) {
            $query->whereHas('incident', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $total = $query->count();
        $byStatus = $query->groupBy('status')->selectRaw('status, count(*) as count')->get();
        $avgDuration = $query->where('status', 'closed')->avg(DB::raw('DATEDIFF(completed_at, created_at)'));
        $overdueCount = $query->where('due_date', '<', now())->where('status', '!=', 'closed')->count();

        return [
            'total_investigations' => $total,
            'average_duration_days' => round($avgDuration, 1),
            'overdue_investigations' => $overdueCount,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get pending investigations
     */
    private function getPendingInvestigations($projectId = null)
    {
        $query = IncidentInvestigation::with(['incident', 'investigator'])
            ->where('status', 'in_progress')
            ->orderBy('due_date', 'asc');

        if ($projectId) {
            $query->whereHas('incident', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        return $query->take(10)->get();
    }

    /**
     * Get overdue investigations
     */
    private function getOverdueInvestigations($projectId = null)
    {
        $query = IncidentInvestigation::with(['incident', 'investigator'])
            ->where('due_date', '<', now())
            ->where('status', '!=', 'closed')
            ->orderBy('due_date', 'asc');

        if ($projectId) {
            $query->whereHas('incident', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        return $query->take(10)->get();
    }

    /**
     * Get investigation trends
     */
    private function getInvestigationTrends($projectId = null)
    {
        $query = DB::table('incident_investigations')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12);

        if ($projectId) {
            $query->join('sor_reports', 'incident_investigations.incident_id', '=', 'sor_reports.id')
                  ->where('sor_reports.project_id', $projectId);
        }

        return $query->get();
    }

    /**
     * Generate investigation report
     */
    public function generateReport(Request $request, $id)
    {
        $investigation = IncidentInvestigation::with([
            'incident',
            'investigator',
            'witnesses',
            'rootCauses',
            'correctiveActions',
            'attachments'
        ])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\PDF::loadView('exports.incident-investigation', [
            'investigation' => $investigation,
            'company' => auth()->user()->company,
        ]);

        return $pdf->download("incident-investigation-{$investigation->id}.pdf");
    }

    /**
     * Get investigation templates
     */
    public function getTemplates()
    {
        $templates = [
            'standard_investigation' => [
                'name' => 'Standard Incident Investigation',
                'description' => 'Comprehensive investigation template for most incidents',
                'sections' => [
                    'incident_details',
                    'witness_interviews',
                    'scene_assessment',
                    'equipment_evaluation',
                    'procedural_review',
                    'root_cause_analysis',
                    'corrective_actions',
                ],
                'estimated_duration' => '7 days',
            ],
            'near_miss' => [
                'name' => 'Near Miss Investigation',
                'description' => 'Focused investigation for near-miss incidents',
                'sections' => [
                    'incident_description',
                    'potential_consequences',
                    'immediate_risks',
                    'preventive_measures',
                ],
                'estimated_duration' => '3 days',
            ],
            'fatal_catastrophic' => [
                'name' => 'Fatal/Catastrophic Investigation',
                'description' => 'Comprehensive investigation for serious incidents',
                'sections' => [
                    'comprehensive_scene_analysis',
                    'multiple_witness_interviews',
                    'expert_consultations',
                    'regulatory_compliance',
                    'detailed_root_cause',
                    'extensive_corrective_actions',
                ],
                'estimated_duration' => '30 days',
            ],
        ];

        return $this->successResponse($templates);
    }

    /**
     * Schedule investigation follow-up
     */
    public function scheduleFollowUp(Request $request, $id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);

        $validated = $request->validate([
            'follow_up_date' => 'required|date|after:today',
            'follow_up_type' => 'required|in:effectiveness_review,compliance_check,additional_investigation',
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $followUp = $investigation->followUps()->create($validated);

        return $this->successResponse($followUp, 'Follow-up scheduled successfully');
    }

    /**
     * Get investigation effectiveness metrics
     */
    public function getEffectivenessMetrics(Request $request)
    {
        $projectId = $request->project_id;
        $dateRange = $request->date_range ?? '90days';

        $dateFrom = match($dateRange) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '1year' => now()->subYear(),
            default => now()->subDays(90),
        };

        $query = IncidentInvestigation::with(['correctiveActions'])
            ->where('created_at', '>=', $dateFrom)
            ->where('status', 'closed');

        if ($projectId) {
            $query->whereHas('incident', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $investigations = $query->get();

        $totalInvestigations = $investigations->count();
        $completedActions = 0;
        $effectiveActions = 0;
        $recurrenceRate = 0;

        foreach ($investigations as $investigation) {
            $actions = $investigation->correctiveActions;
            $completedActions += $actions->where('status', 'completed')->count();
            $effectiveActions += $actions->where('effectiveness_rating', '>=', 4)->count();
        }

        $completionRate = $totalInvestigations > 0 ? ($completedActions / $totalInvestigations) * 100 : 0;
        $effectivenessRate = $completedActions > 0 ? ($effectiveActions / $completedActions) * 100 : 0;

        return $this->successResponse([
            'total_investigations' => $totalInvestigations,
            'corrective_action_completion_rate' => round($completionRate, 1),
            'corrective_action_effectiveness_rate' => round($effectivenessRate, 1),
            'recurrence_rate' => round($recurrenceRate, 2),
        ]);
    }

    /**
     * Display a listing of incidents.
     */
    public function index(Request $request)
    {
        $query = IncidentInvestigation::with(['incident', 'investigator', 'project']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        $incidents = $query->orderBy('created_at', 'desc')->paginate(15);

        return $this->successResponse($incidents);
    }

    /**
     * Display the specified incident investigation.
     */
    public function show($id)
    {
        $investigation = IncidentInvestigation::with([
            'incident', 'investigator', 'rootCauses', 'correctiveActions', 'project'
        ])->findOrFail($id);

        return $this->successResponse($investigation);
    }

    /**
     * Start investigation on an incident.
     */
    public function investigate(Request $request, $id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);

        $validated = $request->validate([
            'investigator_id' => 'required|exists:users,id',
            'investigation_start_date' => 'required|date',
        ]);

        $investigation->update([
            'investigator_id' => $validated['investigator_id'],
            'investigation_start_date' => $validated['investigation_start_date'],
            'status' => 'in_progress',
        ]);

        $this->logActivity('investigation_started', $investigation, $validated);

        return $this->successResponse($investigation->fresh(), 'Investigation started successfully');
    }

    /**
     * Close an incident investigation.
     */
    public function closeIncident(Request $request, $id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);

        $validated = $request->validate([
            'lessons_learned' => 'required|string',
            'investigation_end_date' => 'required|date',
        ]);

        $investigation->update([
            'lessons_learned' => $validated['lessons_learned'],
            'investigation_end_date' => $validated['investigation_end_date'],
            'status' => 'closed',
        ]);

        $this->logActivity('investigation_closed', $investigation, $validated);

        return $this->successResponse($investigation->fresh(), 'Investigation closed successfully');
    }

    /**
     * Remove the specified incident investigation.
     */
    public function destroy($id)
    {
        $investigation = IncidentInvestigation::findOrFail($id);
        $investigation->delete();

        $this->logActivity('investigation_deleted', $investigation);

        return $this->successResponse(null, 'Investigation deleted successfully');
    }
}

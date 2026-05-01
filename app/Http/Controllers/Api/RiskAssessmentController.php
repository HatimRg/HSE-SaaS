<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\RiskAssessment;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiskAssessmentController extends BaseController
{
    /**
     * Get risk assessment dashboard
     */
    public function dashboard(Request $request)
    {
        $projectId = $request->project_id;
        $severity = $request->severity;
        $status = $request->status;

        $query = RiskAssessment::with(['project', 'assessor', 'mitigationMeasures']);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $riskAssessments = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get risk matrix data
        $riskMatrix = $this->generateRiskMatrix($projectId);

        // Get high priority risks
        $highPriorityRisks = $this->getHighPriorityRisks($projectId);

        // Get risk trends
        $riskTrends = $this->getRiskTrends($projectId);

        // Get overdue assessments
        $overdueAssessments = $this->getOverdueAssessments($projectId);

        return $this->successResponse([
            'assessments' => $riskAssessments,
            'risk_matrix' => $riskMatrix,
            'high_priority_risks' => $highPriorityRisks,
            'risk_trends' => $riskTrends,
            'overdue_assessments' => $overdueAssessments,
        ]);
    }

    /**
     * Create new risk assessment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'risk_category' => 'required|in:safety,health,environment,security,operational',
            'likelihood' => 'required|integer|min:1|max:5',
            'severity' => 'required|integer|min:1|max:5',
            'risk_level' => 'required|in:low,medium,high,critical',
            'affected_areas' => 'required|array',
            'affected_personnel' => 'required|array',
            'existing_controls' => 'nullable|array',
            'assessment_date' => 'required|date',
            'review_date' => 'required|date|after:assessment_date',
            'assessor_id' => 'required|exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|max:10240',
            'attachments.*.description' => 'required|string',
        ]);

        // Calculate risk score
        $riskScore = $validated['likelihood'] * $validated['severity'];

        $riskAssessment = RiskAssessment::create([
            'project_id' => $validated['project_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'risk_category' => $validated['risk_category'],
            'likelihood' => $validated['likelihood'],
            'severity' => $validated['severity'],
            'risk_score' => $riskScore,
            'risk_level' => $validated['risk_level'],
            'affected_areas' => $validated['affected_areas'],
            'affected_personnel' => $validated['affected_personnel'],
            'existing_controls' => $validated['existing_controls'] ?? [],
            'assessment_date' => $validated['assessment_date'],
            'review_date' => $validated['review_date'],
            'assessor_id' => $validated['assessor_id'],
            'status' => 'pending_review',
        ]);

        // Handle file attachments
        if (isset($validated['attachments'])) {
            foreach ($validated['attachments'] as $attachment) {
                $path = $attachment['file']->store('risk-assessments', 'public');
                $riskAssessment->attachments()->create([
                    'file_name' => $attachment['file']->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $attachment['file']->getSize(),
                    'description' => $attachment['description'],
                ]);
            }
        }

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($riskAssessment)
            ->log('Risk assessment created');

        return $this->successResponse($riskAssessment->load(['project', 'assessor']), 'Risk assessment created successfully');
    }

    /**
     * Update risk assessment
     */
    public function update(Request $request, $id)
    {
        $riskAssessment = RiskAssessment::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'likelihood' => 'sometimes|integer|min:1|max:5',
            'severity' => 'sometimes|integer|min:1|max:5',
            'risk_level' => 'sometimes|in:low,medium,high,critical',
            'affected_areas' => 'sometimes|array',
            'affected_personnel' => 'sometimes|array',
            'existing_controls' => 'sometimes|array',
            'review_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending_review,approved,rejected,mitigated,monitored,closed',
        ]);

        // Recalculate risk score if likelihood or severity changed
        if (isset($validated['likelihood']) || isset($validated['severity'])) {
            $likelihood = $validated['likelihood'] ?? $riskAssessment->likelihood;
            $severity = $validated['severity'] ?? $riskAssessment->severity;
            $validated['risk_score'] = $likelihood * $severity;
        }

        $riskAssessment->update($validated);

        return $this->successResponse($riskAssessment->fresh(), 'Risk assessment updated successfully');
    }

    /**
     * Generate risk matrix
     */
    private function generateRiskMatrix($projectId = null)
    {
        $query = RiskAssessment::query();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $assessments = $query->get();

        $matrix = [];
        for ($severity = 1; $severity <= 5; $severity++) {
            for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
                $matrix[$severity][$likelihood] = $assessments
                    ->where('severity', $severity)
                    ->where('likelihood', $likelihood)
                    ->count();
            }
        }

        return $matrix;
    }

    /**
     * Get high priority risks
     */
    private function getHighPriorityRisks($projectId = null)
    {
        $query = RiskAssessment::with(['project', 'assessor'])
            ->whereIn('risk_level', ['high', 'critical'])
            ->where('status', '!=', 'mitigated')
            ->orderBy('risk_score', 'desc');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->take(10)->get();
    }

    /**
     * Get risk trends
     */
    private function getRiskTrends($projectId = null)
    {
        $query = DB::table('risk_assessments')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, AVG(risk_score) as avg_score')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }

    /**
     * Get overdue assessments
     */
    private function getOverdueAssessments($projectId = null)
    {
        $query = RiskAssessment::with(['project', 'assessor'])
            ->where('review_date', '<', now())
            ->where('status', '!=', 'closed')
            ->orderBy('review_date', 'asc');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->take(10)->get();
    }

    /**
     * Add mitigation measure
     */
    public function addMitigationMeasure(Request $request, $id)
    {
        $riskAssessment = RiskAssessment::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'responsible_person_id' => 'required|exists:users,id',
            'target_date' => 'required|date',
            'cost_estimate' => 'nullable|numeric',
            'resources_required' => 'nullable|array',
        ]);

        $mitigationMeasure = $riskAssessment->mitigationMeasures()->create($validated);

        // Update risk assessment status if mitigation is added
        if ($riskAssessment->status === 'pending_review') {
            $riskAssessment->update(['status' => 'mitigated']);
        }

        return $this->successResponse($mitigationMeasure, 'Mitigation measure added successfully');
    }

    /**
     * Get risk assessment statistics
     */
    public function statistics(Request $request)
    {
        $projectId = $request->project_id;

        $query = RiskAssessment::query();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $total = $query->count();
        $byStatus = $query->groupBy('status')->selectRaw('status, count(*) as count')->get();
        $byCategory = $query->groupBy('risk_category')->selectRaw('risk_category, count(*) as count')->get();
        $byLevel = $query->groupBy('risk_level')->selectRaw('risk_level, count(*) as count')->get();

        // Calculate average risk score
        $avgRiskScore = $query->avg('risk_score');

        // Get completion rate
        $completed = $query->whereIn('status', ['mitigated', 'closed'])->count();
        $completionRate = $total > 0 ? ($completed / $total) * 100 : 0;

        return $this->successResponse([
            'total_assessments' => $total,
            'average_risk_score' => round($avgRiskScore, 2),
            'completion_rate' => round($completionRate, 1),
            'by_status' => $byStatus,
            'by_category' => $byCategory,
            'by_level' => $byLevel,
        ]);
    }

    /**
     * Generate risk register report
     */
    public function generateRiskRegister(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'risk_level' => 'nullable|in:low,medium,high,critical',
            'status' => 'nullable|in:pending_review,approved,rejected,mitigated,monitored,closed',
            'format' => 'required|in:pdf,excel',
        ]);

        $query = RiskAssessment::with(['project', 'assessor', 'mitigationMeasures']);

        if ($validated['project_id'] ?? null) {
            $query->where('project_id', $validated['project_id']);
        }

        if ($validated['risk_level'] ?? null) {
            $query->where('risk_level', $validated['risk_level']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        $riskAssessments = $query->get();

        if ($validated['format'] === 'pdf') {
            $pdf = \Barryvdh\DomPDF\PDF::loadView('exports.risk-register', [
                'assessments' => $riskAssessments,
                'company' => auth()->user()->company,
            ]);

            return $pdf->download('risk-register.pdf');
        } else {
            // Excel export logic here
            return response()->json(['message' => 'Excel export coming soon']);
        }
    }

    /**
     * Get risk heat map data
     */
    public function riskHeatMap(Request $request)
    {
        $projectId = $request->project_id;

        $query = RiskAssessment::with(['project']);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $assessments = $query->get();

        // Group by project and calculate average risk scores
        $projectRisks = $assessments->groupBy('project_id')->map(function ($projectAssessments) {
            $project = $projectAssessments->first()->project;
            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'location' => $project->location,
                'total_assessments' => $projectAssessments->count(),
                'high_risk_count' => $projectAssessments->whereIn('risk_level', ['high', 'critical'])->count(),
                'average_risk_score' => $projectAssessments->avg('risk_score'),
                'critical_risks' => $projectAssessments->where('risk_level', 'critical')->count(),
            ];
        });

        return $this->successResponse($projectRisks->values());
    }

    /**
     * Schedule risk assessment review
     */
    public function scheduleReview(Request $request, $id)
    {
        $riskAssessment = RiskAssessment::findOrFail($id);

        $validated = $request->validate([
            'review_date' => 'required|date|after:today',
            'reviewer_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $riskAssessment->update([
            'review_date' => $validated['review_date'],
            'reviewer_id' => $validated['reviewer_id'],
            'review_notes' => $validated['notes'],
            'status' => 'scheduled_review',
        ]);

        // Send notification to reviewer
        // Notification logic here

        return $this->successResponse($riskAssessment->fresh(), 'Review scheduled successfully');
    }

    /**
     * Display a listing of risk assessments.
     */
    public function index(Request $request)
    {
        $query = RiskAssessment::with(['project', 'assessor']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->risk_level) {
            $query->where('risk_level', $request->risk_level);
        }

        $assessments = $query->orderBy('created_at', 'desc')->paginate(15);

        return $this->successResponse($assessments);
    }

    /**
     * Display the specified risk assessment.
     */
    public function show($id)
    {
        $assessment = RiskAssessment::with(['project', 'assessor', 'mitigationMeasures'])->findOrFail($id);

        return $this->successResponse($assessment);
    }

    /**
     * Remove the specified risk assessment.
     */
    public function destroy($id)
    {
        $assessment = RiskAssessment::findOrFail($id);
        $assessment->delete();

        $this->logActivity('risk_assessment_deleted', $assessment);

        return $this->successResponse(null, 'Risk assessment deleted successfully');
    }

    /**
     * Get risk matrix data.
     */
    public function matrix(Request $request)
    {
        $projectId = $request->project_id;

        $query = RiskAssessment::query();
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $assessments = $query->get();

        $matrix = [];
        for ($severity = 1; $severity <= 5; $severity++) {
            for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
                $score = $severity * $likelihood;
                $count = $assessments->where('severity', $severity)
                    ->where('likelihood', $likelihood)
                    ->count();

                $matrix[] = [
                    'severity' => $severity,
                    'likelihood' => $likelihood,
                    'score' => $score,
                    'level' => $score >= 20 ? 'critical' : ($score >= 12 ? 'high' : ($score >= 6 ? 'medium' : 'low')),
                    'count' => $count,
                ];
            }
        }

        return $this->successResponse($matrix);
    }
}

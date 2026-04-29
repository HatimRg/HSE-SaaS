<?php

namespace App\Http\Controllers\Api;

use App\Models\KpiReport;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KpiReportController extends BaseController
{
    /**
     * List KPI reports.
     */
    public function index(Request $request)
    {
        $query = KpiReport::with(['project', 'creator', 'approver']);

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by period
        if ($request->has('period_start')) {
            $query->where('period_start', '>=', $request->period_start);
        }
        if ($request->has('period_end')) {
            $query->where('period_end', '<=', $request->period_end);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('project', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $this->paginatedResponse($query->latest(), $request, 'kpi_reports_list');
    }

    /**
     * Get KPI summary.
     */
    public function summary(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $projectId = $request->get('project_id');
        $period = $request->get('period', 'month');

        $cacheKey = "kpi_summary:{$period}:" . ($projectId ?? 'all');

        $data = $this->cache->remember($cacheKey, function () use ($companyId, $projectId, $period) {
            $query = KpiReport::where('company_id', $companyId);

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            switch ($period) {
                case 'week':
                    $query->whereBetween('period_start', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereBetween('period_start', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                case 'quarter':
                    $query->whereBetween('period_start', [now()->startOfQuarter(), now()->endOfQuarter()]);
                    break;
                case 'year':
                    $query->whereBetween('period_start', [now()->startOfYear(), now()->endOfYear()]);
                    break;
            }

            $totals = $query->select(
                DB::raw('SUM(total_hours) as total_hours'),
                DB::raw('SUM(injuries) as injuries'),
                DB::raw('SUM(first_aids) as first_aids'),
                DB::raw('SUM(near_misses) as near_misses'),
                DB::raw('SUM(observations) as observations'),
                DB::raw('SUM(lost_time_incidents) as lost_time_incidents'),
                DB::raw('SUM(environmental_incidents) as environmental_incidents'),
                DB::raw('COUNT(*) as reports_count')
            )->first();

            $totalHours = max($totals->total_hours, 1);

            return [
                'total_hours' => round($totals->total_hours ?? 0, 2),
                'injuries' => (int) ($totals->injuries ?? 0),
                'first_aids' => (int) ($totals->first_aids ?? 0),
                'near_misses' => (int) ($totals->near_misses ?? 0),
                'observations' => (int) ($totals->observations ?? 0),
                'lost_time_incidents' => (int) ($totals->lost_time_incidents ?? 0),
                'environmental_incidents' => (int) ($totals->environmental_incidents ?? 0),
                'reports_count' => (int) ($totals->reports_count ?? 0),
                'trir' => round((($totals->injuries ?? 0) * 200000) / $totalHours, 2),
                'frequency_rate' => round((($totals->injuries ?? 0) * 1000000) / $totalHours, 2),
            ];
        }, 120);

        return $this->successResponse($data);
    }

    /**
     * Get single KPI report.
     */
    public function show($id)
    {
        $report = KpiReport::with(['project', 'creator', 'approver'])->findOrFail($id);

        return $this->successResponse([
            ...$report->toArray(),
            'trir' => $report->calculateTrir(),
            'frequency_rate' => $report->calculateFrequencyRate(),
            'severity_rate' => $report->calculateSeverityRate(),
            'can_edit' => $report->canBeEdited() && auth()->user()->id === $report->user_id,
        ]);
    }

    /**
     * Create KPI report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'total_hours' => 'required|numeric|min:0',
            'injuries' => 'integer|min:0',
            'first_aids' => 'integer|min:0',
            'near_misses' => 'integer|min:0',
            'observations' => 'integer|min:0',
            'lost_time_incidents' => 'integer|min:0',
            'environmental_incidents' => 'integer|min:0',
            'vehicles_damaged' => 'integer|min:0',
            'vehicles_lost' => 'integer|min:0',
            'manpower_count' => 'integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        // Check for duplicate
        $exists = KpiReport::where('project_id', $validated['project_id'])
            ->where('period_start', $validated['period_start'])
            ->where('period_end', $validated['period_end'])
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($exists) {
            return $this->errorResponse('A report already exists for this project and period', 422);
        }

        $report = KpiReport::create([
            ...$validated,
            'status' => 'draft',
        ]);

        $this->logActivity('kpi_created', $report);
        $this->clearCache('kpi_reports');

        return $this->successResponse($report, 'KPI report created successfully', 201);
    }

    /**
     * Update KPI report.
     */
    public function update(Request $request, $id)
    {
        $report = KpiReport::findOrFail($id);

        if (!$report->canBeEdited()) {
            return $this->errorResponse('Cannot edit submitted or approved report', 403);
        }

        if ($report->user_id !== auth()->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $validated = $request->validate([
            'total_hours' => 'sometimes|numeric|min:0',
            'injuries' => 'sometimes|integer|min:0',
            'first_aids' => 'sometimes|integer|min:0',
            'near_misses' => 'sometimes|integer|min:0',
            'observations' => 'sometimes|integer|min:0',
            'lost_time_incidents' => 'sometimes|integer|min:0',
            'environmental_incidents' => 'sometimes|integer|min:0',
            'vehicles_damaged' => 'sometimes|integer|min:0',
            'vehicles_lost' => 'sometimes|integer|min:0',
            'manpower_count' => 'sometimes|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        $report->update($validated);

        $this->logActivity('kpi_updated', $report);
        $this->clearCache('kpi_reports');

        return $this->successResponse($report, 'KPI report updated successfully');
    }

    /**
     * Submit KPI report for approval.
     */
    public function submit($id)
    {
        $report = KpiReport::findOrFail($id);

        if (!$report->canBeEdited()) {
            return $this->errorResponse('Report already submitted or approved', 422);
        }

        if ($report->user_id !== auth()->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $report->update(['status' => 'submitted']);

        $this->logActivity('kpi_submitted', $report);
        $this->clearCache('kpi_reports');

        // Notify approvers
        $this->notifyApprovers($report);

        return $this->successResponse($report, 'KPI report submitted for approval');
    }

    /**
     * Approve KPI report.
     */
    public function approve(Request $request, $id)
    {
        $report = KpiReport::findOrFail($id);

        if ($report->status !== 'submitted') {
            return $this->errorResponse('Report must be submitted first', 422);
        }

        $user = auth()->user();
        if (!$user->permissions['can_approve_kpi'] ?? false) {
            return $this->errorResponse('Unauthorized to approve KPI reports', 403);
        }

        $report->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->logActivity('kpi_approved', $report);
        $this->clearCache('kpi_reports');

        // Notify creator
        \App\Models\Notification::create([
            'company_id' => $report->company_id,
            'user_id' => $report->user_id,
            'title' => 'KPI Report Approved',
            'message' => "Your KPI report for {$report->project->name} has been approved",
            'type' => 'kpi',
            'urgency' => 'info',
            'action_url' => "/kpi-reports/{$report->id}",
        ]);

        return $this->successResponse($report, 'KPI report approved');
    }

    /**
     * Reject KPI report.
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string']);

        $report = KpiReport::findOrFail($id);

        if ($report->status !== 'submitted') {
            return $this->errorResponse('Report must be submitted first', 422);
        }

        $user = auth()->user();
        if (!$user->permissions['can_approve_kpi'] ?? false) {
            return $this->errorResponse('Unauthorized to reject KPI reports', 403);
        }

        $report->update([
            'status' => 'rejected',
            'remarks' => $report->remarks . "\n\nRejection reason: " . $validated['reason'],
        ]);

        $this->logActivity('kpi_rejected', $report, ['reason' => $validated['reason']]);
        $this->clearCache('kpi_reports');

        // Notify creator
        \App\Models\Notification::create([
            'company_id' => $report->company_id,
            'user_id' => $report->user_id,
            'title' => 'KPI Report Rejected',
            'message' => "Your KPI report was rejected: {$validated['reason']}",
            'type' => 'kpi',
            'urgency' => 'warning',
            'action_url' => "/kpi-reports/{$report->id}",
        ]);

        return $this->successResponse($report, 'KPI report rejected');
    }

    /**
     * Delete KPI report.
     */
    public function destroy($id)
    {
        $report = KpiReport::findOrFail($id);

        if (!$report->canBeEdited()) {
            return $this->errorResponse('Cannot delete submitted or approved report', 403);
        }

        if ($report->user_id !== auth()->user()->id && !auth()->user()->permissions['is_admin']) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $report->delete();

        $this->logActivity('kpi_deleted', $report);
        $this->clearCache('kpi_reports');

        return $this->successResponse(null, 'KPI report deleted successfully');
    }

    /**
     * Notify approvers about submitted report.
     */
    private function notifyApprovers(KpiReport $report): void
    {
        $approvers = \App\Models\User::where('company_id', $report->company_id)
            ->whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'hse_director', 'hse_manager']);
            })
            ->where('is_active', true)
            ->get();

        foreach ($approvers as $approver) {
            \App\Models\Notification::create([
                'company_id' => $report->company_id,
                'user_id' => $approver->id,
                'title' => 'KPI Report Pending Approval',
                'message' => "New KPI report from {$report->creator->name} for {$report->project->name}",
                'type' => 'approval',
                'urgency' => 'warning',
                'action_url' => "/kpi-reports/{$report->id}",
            ]);
        }
    }
}

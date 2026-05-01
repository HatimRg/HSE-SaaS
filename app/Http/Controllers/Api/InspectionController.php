<?php

namespace App\Http\Controllers\Api;

use App\Models\Inspection;
use App\Models\WorkPermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InspectionController extends BaseController
{
    /**
     * Display a listing of inspections.
     */
    public function index(Request $request)
    {
        $inspections = Inspection::with(['inspector', 'project', 'workPermit'])
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->inspection_type, function ($query, $type) {
                $query->where('inspection_type', $type);
            })
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('scheduled_date', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('scheduled_date', '<=', $date);
            })
            ->orderBy('scheduled_date', 'desc')
            ->paginate(15);

        return $this->successResponse($inspections);
    }

    /**
     * Store a newly created inspection.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'inspection_type' => 'required|in:safety,quality,environmental,electrical,structural,fire',
            'scheduled_date' => 'required|date',
            'inspector_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'work_permit_id' => 'nullable|exists:work_permits,id',
            'location' => 'required|string|max:255',
            'checklist_items' => 'required|array|min:1',
            'checklist_items.*.description' => 'required|string|max:255',
            'checklist_items.*.category' => 'required|string|max:100',
            'checklist_items.*.required' => 'boolean',
        ]);

        $inspection = Inspection::create($validated);

        // Create checklist items
        foreach ($validated['checklist_items'] as $item) {
            $inspection->checklistItems()->create($item);
        }

        $inspection->load(['inspector', 'project', 'checklistItems']);

        $this->logActivity('inspection_created', $inspection, $validated);

        return $this->successResponse($inspection, 'Inspection created successfully');
    }

    /**
     * Display the specified inspection.
     */
    public function show(Inspection $inspection)
    {
        $inspection->load([
            'inspector', 
            'project', 
            'workPermit',
            'checklistItems.findings',
            'findings.photos'
        ]);

        return $this->successResponse($inspection);
    }

    /**
     * Update the specified inspection.
     */
    public function update(Request $request, Inspection $inspection)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'inspection_type' => 'sometimes|in:safety,quality,environmental,electrical,structural,fire',
            'scheduled_date' => 'sometimes|date',
            'inspector_id' => 'sometimes|exists:users,id',
            'project_id' => 'sometimes|exists:projects,id',
            'work_permit_id' => 'nullable|exists:work_permits,id',
            'location' => 'sometimes|string|max:255',
            'status' => ['sometimes', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after:actual_start_time',
            'weather_conditions' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $inspection->update($validated);

        $inspection->load(['inspector', 'project', 'checklistItems']);

        $this->logActivity('inspection_updated', $inspection, $validated);

        return $this->successResponse($inspection, 'Inspection updated successfully');
    }

    /**
     * Remove the specified inspection.
     */
    public function destroy(Inspection $inspection)
    {
        // Delete associated files
        foreach ($inspection->findings as $finding) {
            foreach ($finding->photos as $photo) {
                Storage::disk('public')->delete($photo->file_path);
            }
        }

        $inspection->delete();

        $this->logActivity('inspection_deleted', $inspection);

        return $this->successResponse(null, 'Inspection deleted successfully');
    }

    /**
     * Start inspection.
     */
    public function start(Inspection $inspection)
    {
        if ($inspection->status !== 'scheduled') {
            return $this->errorResponse('Inspection cannot be started', 400);
        }

        $inspection->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);

        $this->logActivity('inspection_started', $inspection);

        return $this->successResponse($inspection, 'Inspection started successfully');
    }

    /**
     * Complete inspection.
     */
    public function complete(Request $request, Inspection $inspection)
    {
        if ($inspection->status !== 'in_progress') {
            return $this->errorResponse('Inspection must be in progress to complete', 400);
        }

        $validated = $request->validate([
            'summary' => 'required|string',
            'overall_rating' => 'required|in:excellent,good,fair,poor,critical',
            'recommendations' => 'nullable|array',
            'recommendations.*.description' => 'required|string',
            'recommendations.*.priority' => 'required|in:low,medium,high,critical',
            'recommendations.*.deadline' => 'nullable|date',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:today',
        ]);

        $inspection->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'summary' => $validated['summary'],
            'overall_rating' => $validated['overall_rating'],
            'follow_up_required' => $validated['follow_up_required'] ?? false,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ]);

        // Create recommendations
        if (!empty($validated['recommendations'])) {
            foreach ($validated['recommendations'] as $rec) {
                $inspection->recommendations()->create($rec);
            }
        }

        $inspection->load(['inspector', 'project', 'recommendations']);

        $this->logActivity('inspection_completed', $inspection, $validated);

        return $this->successResponse($inspection, 'Inspection completed successfully');
    }

    /**
     * Add finding to inspection.
     */
    public function addFinding(Request $request, Inspection $inspection)
    {
        if ($inspection->status !== 'in_progress') {
            return $this->errorResponse('Findings can only be added to inspections in progress', 400);
        }

        $validated = $request->validate([
            'checklist_item_id' => 'required|exists:checklist_items,id',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'location_description' => 'nullable|string|max:255',
            'immediate_action_required' => 'boolean',
            'corrective_action' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $finding = $inspection->findings()->create([
            'checklist_item_id' => $validated['checklist_item_id'],
            'severity' => $validated['severity'],
            'description' => $validated['description'],
            'location_description' => $validated['location_description'] ?? null,
            'immediate_action_required' => $validated['immediate_action_required'] ?? false,
            'corrective_action' => $validated['corrective_action'] ?? null,
        ]);

        // Handle photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('inspection-photos', 'public');
                $finding->photos()->create([
                    'file_path' => $path,
                    'file_name' => $photo->getClientOriginalName(),
                    'file_type' => $photo->getClientMimeType(),
                    'file_size' => $photo->getSize(),
                ]);
            }
        }

        $finding->load(['checklistItem', 'photos']);

        $this->logActivity('inspection_finding_added', $inspection, $validated);

        return $this->successResponse($finding, 'Finding added successfully');
    }

    /**
     * Update finding.
     */
    public function updateFinding(Request $request, Inspection $inspection, $findingId)
    {
        $finding = $inspection->findings()->findOrFail($findingId);

        $validated = $request->validate([
            'severity' => 'sometimes|in:low,medium,high,critical',
            'description' => 'sometimes|string',
            'location_description' => 'nullable|string|max:255',
            'immediate_action_required' => 'boolean',
            'corrective_action' => 'nullable|string',
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string',
        ]);

        $finding->update($validated);

        $this->logActivity('inspection_finding_updated', $inspection, $validated);

        return $this->successResponse($finding, 'Finding updated successfully');
    }

    /**
     * Generate inspection report.
     */
    public function generateReport(Inspection $inspection)
    {
        if ($inspection->status !== 'completed') {
            return $this->errorResponse('Inspection must be completed to generate report', 400);
        }

        $report = [
            'inspection' => $inspection->load(['inspector', 'project', 'workPermit']),
            'checklist_summary' => $inspection->checklistItems()->with(['findings'])->get()->map(function ($item) {
                return [
                    'description' => $item->description,
                    'category' => $item->category,
                    'findings_count' => $item->findings->count(),
                    'critical_findings' => $item->findings->where('severity', 'critical')->count(),
                    'high_findings' => $item->findings->where('severity', 'high')->count(),
                ];
            }),
            'findings_summary' => [
                'total_findings' => $inspection->findings->count(),
                'by_severity' => $inspection->findings->groupBy('severity')->map->count(),
                'by_category' => $inspection->findings->groupBy('checklistItem.category')->map->count(),
            ],
            'recommendations' => $inspection->recommendations,
            'photos' => $inspection->findings->flatMap->photos,
        ];

        $this->logActivity('inspection_report_generated', $inspection);

        return $this->successResponse($report, 'Inspection report generated successfully');
    }

    /**
     * Get inspection statistics.
     */
    public function statistics(Request $request)
    {
        $query = Inspection::query();

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $stats = [
            'total_inspections' => $query->count(),
            'completed_inspections' => $query->where('status', 'completed')->count(),
            'in_progress_inspections' => $query->where('status', 'in_progress')->count(),
            'upcoming_inspections' => $query->where('status', 'scheduled')->where('scheduled_date', '>', now())->count(),
            'by_type' => $query->selectRaw('inspection_type, COUNT(*) as count')
                ->groupBy('inspection_type')
                ->pluck('count', 'inspection_type'),
            'by_rating' => $query->selectRaw('overall_rating, COUNT(*) as count')
                ->whereNotNull('overall_rating')
                ->groupBy('overall_rating')
                ->pluck('count', 'overall_rating'),
            'findings_stats' => [
                'total_findings' => $query->withCount('findings')->get()->sum('findings_count'),
                'critical_findings' => $query->whereHas('findings', function ($q) {
                    $q->where('severity', 'critical');
                })->count(),
                'high_findings' => $query->whereHas('findings', function ($q) {
                    $q->where('severity', 'high');
                })->count(),
            ],
            'by_month' => $query->selectRaw('YEAR(scheduled_date) as year, MONTH(scheduled_date) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get(),
        ];

        return $this->successResponse($stats);
    }
}

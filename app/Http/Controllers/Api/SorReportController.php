<?php

namespace App\Http\Controllers\Api;

use App\Models\SorReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SorReportController extends BaseController
{
    /**
     * List SOR reports.
     */
    public function index(Request $request)
    {
        $query = SorReport::with(['project', 'reporter', 'responsiblePerson']);

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        // Show only overdue
        if ($request->boolean('overdue')) {
            $query->whereIn('status', ['open', 'in-progress'])
                  ->where('due_date', '<', now());
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this->paginatedResponse($query->latest(), $request, 'sor_reports_list');
    }

    /**
     * Get single SOR report.
     */
    public function show($id)
    {
        $report = SorReport::with(['project', 'reporter', 'responsiblePerson', 'comments.user'])->findOrFail($id);

        return $this->successResponse([
            ...$report->toArray(),
            'is_overdue' => $report->isOverdue(),
        ]);
    }

    /**
     * Create SOR report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:observation,near_miss,incident,hazard,violation,improvement',
            'severity' => 'required|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'responsible_person_id' => 'nullable|exists:users,id',
            'corrective_action' => 'nullable|string',
            'due_date' => 'nullable|date|after_or_equal:date',
        ]);

        $report = SorReport::create([
            ...$validated,
            'reference' => SorReport::generateReference(),
            'status' => 'open',
        ]);

        $this->logActivity('sor_created', $report);
        $this->clearCache('sor_reports_list');

        return $this->successResponse($report, 'SOR report created successfully', 201);
    }

    /**
     * Update SOR report.
     */
    public function update(Request $request, $id)
    {
        $report = SorReport::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:observation,near_miss,incident,hazard,violation,improvement',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|in:open,in-progress,closed,cancelled',
            'responsible_person_id' => 'nullable|exists:users,id',
            'corrective_action' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        // Check permissions for status change
        if (isset($validated['status']) && $validated['status'] !== $report->status) {
            if (!in_array(auth()->user()->role?->name, ['admin', 'hse_director', 'hse_manager', 'responsable'])) {
                unset($validated['status']);
            }
        }

        if ($validated['status'] === 'closed' && $report->status !== 'closed') {
            $validated['completed_at'] = now();
        }

        $report->update($validated);

        $this->logActivity('sor_updated', $report);
        $this->clearCache('sor_reports_list');

        return $this->successResponse($report, 'SOR report updated successfully');
    }

    /**
     * Close SOR report.
     */
    public function close(Request $request, $id)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $report = SorReport::findOrFail($id);

        $report->update([
            'status' => 'closed',
            'completed_at' => now(),
            'corrective_action' => $report->corrective_action . "\n\nResolution: " . $validated['resolution_notes'],
        ]);

        $this->logActivity('sor_closed', $report);
        $this->clearCache('sor_reports_list');

        return $this->successResponse($report, 'SOR report closed successfully');
    }

    /**
     * Delete SOR report.
     */
    public function destroy($id)
    {
        $report = SorReport::findOrFail($id);
        $report->delete();

        $this->logActivity('sor_deleted', $report);
        $this->clearCache('sor_reports_list');

        return $this->successResponse(null, 'SOR report deleted successfully');
    }

    /**
     * Upload photos to SOR.
     */
    public function uploadPhotos(Request $request, $id)
    {
        $report = SorReport::findOrFail($id);

        $request->validate([
            'photos' => 'required|array|max:5',
            'photos.*' => 'image|max:2048|mimes:jpeg,png,jpg',
        ]);

        $photos = $report->photos ?? [];

        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('sor-photos/' . $report->id, 'public');
            $photos[] = $path;
        }

        $report->update(['photos' => $photos]);

        return $this->successResponse(['photos' => $photos], 'Photos uploaded successfully');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\HseEvent;
use App\Models\EventAction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HseEventController extends BaseController
{
    public function index(Request $request)
    {
        $query = HseEvent::with(['project', 'reporter', 'assignee', 'riskItem', 'actions']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('from')) {
            $query->where('occurred_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('occurred_at', '<=', $request->to);
        }

        $query->orderBy('occurred_at', 'desc');

        return $this->paginatedResponse($query, $request, 'hse_events:list');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:observation,near_miss,incident,hazard,violation,improvement,audit,training',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'occurred_at' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'risk_item_id' => 'nullable|exists:risk_items,id',
            'photos' => 'nullable|array',
            'attachments' => 'nullable|array',
        ]);

        $validated['reported_by'] = auth()->id();
        $validated['reference'] = HseEvent::generateReference();

        $event = HseEvent::create($validated);

        $this->logActivity('created', $event);

        return $this->successResponse($event->load(['project', 'reporter', 'assignee']), 'Event created successfully', 201);
    }

    public function show(HseEvent $hseEvent)
    {
        return $this->successResponse($hseEvent->load(['project', 'reporter', 'assignee', 'riskItem', 'actions.assignee']));
    }

    public function update(Request $request, HseEvent $hseEvent)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:observation,near_miss,incident,hazard,violation,improvement,audit,training',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:open,in_progress,closed,verified,cancelled',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'risk_item_id' => 'nullable|exists:risk_items,id',
            'photos' => 'nullable|array',
            'attachments' => 'nullable|array',
        ]);

        $hseEvent->update($validated);

        if (isset($validated['status']) && in_array($validated['status'], ['closed', 'verified'])) {
            $hseEvent->update([
                $validated['status'] === 'closed' ? 'closed_at' : 'verified_at' => now(),
            ]);
        }

        $this->logActivity('updated', $hseEvent);

        return $this->successResponse($hseEvent->fresh()->load(['project', 'reporter', 'assignee', 'actions']), 'Event updated successfully');
    }

    public function destroy(HseEvent $hseEvent)
    {
        $hseEvent->delete();
        $this->logActivity('deleted', $hseEvent);
        return $this->successResponse(null, 'Event deleted successfully');
    }

    public function addAction(Request $request, HseEvent $hseEvent)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'type' => 'required|in:corrective,preventive',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $action = $hseEvent->actions()->create([
            ...$validated,
            'company_id' => $hseEvent->company_id,
        ]);

        $this->logActivity('action_added', $hseEvent);

        return $this->successResponse($action->load('assignee'), 'Action added successfully', 201);
    }

    public function stats(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $projectId = $request->get('project_id');

        $query = HseEvent::where('company_id', $companyId);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $stats = [
            'total' => $query->count(),
            'by_type' => (clone $query)->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type'),
            'by_severity' => (clone $query)->selectRaw('severity, count(*) as count')->groupBy('severity')->pluck('count', 'severity'),
            'by_status' => (clone $query)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            'open' => (clone $query)->where('status', 'open')->count(),
            'overdue' => (clone $query)->where('status', 'open')->where('due_date', '<', now())->count(),
        ];

        return $this->successResponse($stats);
    }
}

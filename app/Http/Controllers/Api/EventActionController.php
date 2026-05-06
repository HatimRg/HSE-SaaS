<?php

namespace App\Http\Controllers\Api;

use App\Models\EventAction;
use Illuminate\Http\Request;

class EventActionController extends BaseController
{
    public function index(Request $request)
    {
        $query = EventAction::with(['source', 'assignee', 'verifier']);

        if ($request->has('source_type')) {
            $query->where('source_type', $request->source_type);
        }
        if ($request->has('source_id')) {
            $query->where('source_id', $request->source_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->has('overdue') && filter_var($request->overdue, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('status', '!=', 'completed')
                  ->where('status', '!=', 'verified')
                  ->where('due_date', '<', now());
        }

        $query->orderBy('due_date', 'asc');

        return $this->paginatedResponse($query, $request, 'event_actions:list');
    }

    public function show(EventAction $eventAction)
    {
        return $this->successResponse($eventAction->load(['source', 'assignee', 'verifier']));
    }

    public function update(Request $request, EventAction $eventAction)
    {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:corrective,preventive',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:open,in_progress,completed,verified,overdue',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'completed') {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] === 'verified') {
                $validated['verified_at'] = now();
                $validated['verified_by'] = auth()->id();
            }
        }

        $eventAction->update($validated);
        $this->logActivity('updated', $eventAction);

        return $this->successResponse($eventAction->fresh()->load(['source', 'assignee', 'verifier']), 'Action updated successfully');
    }

    public function destroy(EventAction $eventAction)
    {
        $eventAction->delete();
        $this->logActivity('deleted', $eventAction);
        return $this->successResponse(null, 'Action deleted successfully');
    }

    public function stats(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $stats = [
            'total' => EventAction::where('company_id', $companyId)->count(),
            'open' => EventAction::where('company_id', $companyId)->where('status', 'open')->count(),
            'in_progress' => EventAction::where('company_id', $companyId)->where('status', 'in_progress')->count(),
            'completed' => EventAction::where('company_id', $companyId)->where('status', 'completed')->count(),
            'overdue' => EventAction::where('company_id', $companyId)
                ->whereNotIn('status', ['completed', 'verified'])
                ->where('due_date', '<', now())->count(),
            'closure_rate' => $this->calculateClosureRate($companyId),
        ];

        return $this->successResponse($stats);
    }

    private function calculateClosureRate(int $companyId): float
    {
        $total = EventAction::where('company_id', $companyId)->count();
        $closed = EventAction::where('company_id', $companyId)->whereIn('status', ['completed', 'verified'])->count();
        return $total > 0 ? round(($closed / $total) * 100, 1) : 0;
    }
}

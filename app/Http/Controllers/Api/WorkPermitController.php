<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkPermit;
use Illuminate\Http\Request;

class WorkPermitController extends BaseController
{
    /**
     * List work permits.
     */
    public function index(Request $request)
    {
        $query = WorkPermit::with(['project', 'requester', 'approver']);

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

        // Show only valid/approved
        if ($request->boolean('valid_only')) {
            $query->where('status', 'approved')
                  ->where('expiry_date', '>', now());
        }

        // Show expiring soon
        if ($request->boolean('expiring_soon')) {
            $query->where('status', 'approved')
                  ->whereBetween('expiry_date', [now(), now()->addDays(3)]);
        }

        return $this->paginatedResponse($query->latest(), $request, 'work_permits_list');
    }

    /**
     * Get permit types.
     */
    public function types()
    {
        return $this->successResponse(WorkPermit::getTypes());
    }

    /**
     * Get single permit.
     */
    public function show($id)
    {
        $permit = WorkPermit::with(['project', 'requester', 'approver', 'issuingAuthority', 'fireWatchAssignee', 'originalPermit'])->findOrFail($id);

        return $this->successResponse([
            ...$permit->toArray(),
            'is_valid' => $permit->isValid(),
            'is_expired' => $permit->isExpired(),
            'expires_soon' => $permit->expiresSoon(),
        ]);
    }

    /**
     * Create permit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:hot_work,working_at_height,confined_space,electrical,excavation,demolition,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'issued_date' => 'required|date',
            'expiry_date' => 'required|date|after:issued_date',
            'hazards_identified' => 'nullable|string',
            'precautions_taken' => 'nullable|string',
            'fire_watch_required' => 'boolean',
        ]);

        $permit = WorkPermit::create([
            ...$validated,
            'permit_number' => WorkPermit::generatePermitNumber(),
            'status' => 'pending',
        ]);

        $this->logActivity('permit_created', $permit);
        $this->clearCache('work_permits_list');

        return $this->successResponse($permit, 'Work permit created successfully', 201);
    }

    /**
     * Update permit.
     */
    public function update(Request $request, $id)
    {
        $permit = WorkPermit::findOrFail($id);

        if (!in_array($permit->status, ['draft', 'pending', 'rejected'])) {
            return $this->errorResponse('Cannot edit approved or suspended permit', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'issued_date' => 'sometimes|date',
            'expiry_date' => 'sometimes|date',
            'hazards_identified' => 'nullable|string',
            'precautions_taken' => 'nullable|string',
            'fire_watch_required' => 'sometimes|boolean',
            'fire_watch_assigned_to' => 'nullable|exists:users,id',
        ]);

        $permit->update($validated);

        $this->logActivity('permit_updated', $permit);
        $this->clearCache('work_permits_list');

        return $this->successResponse($permit, 'Work permit updated successfully');
    }

    /**
     * Approve permit.
     */
    public function approve(Request $request, $id)
    {
        $permit = WorkPermit::findOrFail($id);

        if ($permit->status !== 'pending') {
            return $this->errorResponse('Permit is not pending approval', 422);
        }

        $permit->update([
            'status' => 'approved',
            'approver_id' => auth()->id(),
            'approved_at' => now(),
            'issuing_authority_id' => auth()->id(),
        ]);

        $this->logActivity('permit_approved', $permit);
        $this->clearCache('work_permits_list');

        // Notify requester
        \App\Models\Notification::create([
            'company_id' => $permit->company_id,
            'user_id' => $permit->user_id,
            'title' => 'Work Permit Approved',
            'message' => "Your permit #{$permit->permit_number} has been approved",
            'type' => 'permit',
            'urgency' => 'info',
            'action_url' => "/work-permits/{$permit->id}",
        ]);

        return $this->successResponse($permit, 'Work permit approved');
    }

    /**
     * Reject permit.
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string']);
        $permit = WorkPermit::findOrFail($id);

        if ($permit->status !== 'pending') {
            return $this->errorResponse('Permit is not pending approval', 422);
        }

        $permit->update([
            'status' => 'rejected',
            'suspension_reason' => $validated['reason'],
        ]);

        $this->logActivity('permit_rejected', $permit, ['reason' => $validated['reason']]);
        $this->clearCache('work_permits_list');

        return $this->successResponse($permit, 'Work permit rejected');
    }

    /**
     * Suspend permit.
     */
    public function suspend(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string']);
        $permit = WorkPermit::findOrFail($id);

        if ($permit->status !== 'approved') {
            return $this->errorResponse('Only approved permits can be suspended', 422);
        }

        $permit->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'],
        ]);

        $this->logActivity('permit_suspended', $permit, ['reason' => $validated['reason']]);
        $this->clearCache('work_permits_list');

        return $this->successResponse($permit, 'Work permit suspended');
    }

    /**
     * Renew permit.
     */
    public function renew(Request $request, $id)
    {
        $validated = $request->validate([
            'expiry_date' => 'required|date|after:today',
            'reason' => 'required|string',
        ]);

        $oldPermit = WorkPermit::findOrFail($id);

        $newPermit = WorkPermit::create([
            'company_id' => $oldPermit->company_id,
            'project_id' => $oldPermit->project_id,
            'user_id' => auth()->id(),
            'permit_number' => WorkPermit::generatePermitNumber(),
            'type' => $oldPermit->type,
            'title' => $oldPermit->title,
            'description' => $oldPermit->description,
            'location' => $oldPermit->location,
            'issued_date' => now(),
            'expiry_date' => $validated['expiry_date'],
            'status' => 'pending',
            'renewal_of' => $oldPermit->id,
        ]);

        $this->logActivity('permit_renewed', $newPermit, ['previous_permit_id' => $id]);
        $this->clearCache('work_permits_list');

        return $this->successResponse($newPermit, 'Work permit renewal created', 201);
    }

    /**
     * Delete permit.
     */
    public function destroy($id)
    {
        $permit = WorkPermit::findOrFail($id);
        $permit->delete();

        $this->logActivity('permit_deleted', $permit);
        $this->clearCache('work_permits_list');

        return $this->successResponse(null, 'Work permit deleted successfully');
    }
}

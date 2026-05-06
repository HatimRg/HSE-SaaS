<?php

namespace App\Observers;

use App\Models\WorkPermit;
use App\Models\Notification;

class WorkPermitObserver
{
    public function created(WorkPermit $permit): void
    {
        // Notify approvers when a new permit is submitted
        $approvers = \App\Models\User::where('company_id', $permit->company_id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['permit_approver', 'safety_manager', 'project_manager']))
            ->get();

        foreach ($approvers as $approver) {
            Notification::create([
                'company_id' => $permit->company_id,
                'user_id' => $approver->id,
                'title' => "New permit request: {$permit->permit_number}",
                'message' => "A {$permit->type} permit has been submitted for {$permit->location} and requires your approval.",
                'type' => 'permit',
                'urgency' => 'warning',
                'action_url' => "/permits?highlight={$permit->id}",
                'action_text' => 'Review Permit',
                'dedupe_key' => "permit_new_{$permit->id}_{$approver->id}",
                'data' => ['permit_id' => $permit->id, 'permit_type' => $permit->type],
            ]);
        }
    }

    public function updated(WorkPermit $permit): void
    {
        if (!$permit->isDirty('status')) {
            return;
        }

        // Notify the requester when permit is approved/rejected
        if ($permit->requested_by) {
            $urgency = $permit->status === 'approved' ? 'info' : 'urgent';
            $message = $permit->status === 'approved'
                ? "Your {$permit->type} permit {$permit->permit_number} has been approved."
                : "Your {$permit->type} permit {$permit->permit_number} has been rejected.";

            Notification::create([
                'company_id' => $permit->company_id,
                'user_id' => $permit->requested_by,
                'title' => "Permit {$permit->permit_number} {$permit->status}",
                'message' => $message,
                'type' => 'permit',
                'urgency' => $urgency,
                'action_url' => "/permits?highlight={$permit->id}",
                'action_text' => 'View Permit',
                'dedupe_key' => "permit_status_{$permit->id}_{$permit->status}",
                'data' => ['permit_id' => $permit->id, 'new_status' => $permit->status],
            ]);
        }

        // Notify about expiring permits
        if ($permit->status === 'approved' && $permit->expiry_date) {
            $daysUntilExpiry = now()->diffInDays($permit->expiry_date, false);
            if ($daysUntilExpiry <= 2 && $daysUntilExpiry >= 0) {
                $permitHolder = $permit->requested_by ?? $permit->supervisor_id;
                if ($permitHolder) {
                    Notification::create([
                        'company_id' => $permit->company_id,
                        'user_id' => $permitHolder,
                        'title' => "Permit expiring soon: {$permit->permit_number}",
                        'message' => "Your {$permit->type} permit expires in {$daysUntilExpiry} day(s). Please arrange renewal or closure.",
                        'type' => 'permit',
                        'urgency' => 'warning',
                        'action_url' => "/permits?highlight={$permit->id}",
                        'action_text' => 'View Permit',
                        'dedupe_key' => "permit_expiring_{$permit->id}",
                        'data' => ['permit_id' => $permit->id, 'days_until_expiry' => $daysUntilExpiry],
                    ]);
                }
            }
        }
    }
}

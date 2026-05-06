<?php

namespace App\Observers;

use App\Models\HseEvent;
use App\Models\Notification;

class HseEventObserver
{
    public function created(HseEvent $event): void
    {
        $urgency = match ($event->severity) {
            'critical' => 'critical',
            'high' => 'urgent',
            'medium' => 'warning',
            default => 'info',
        };

        $type = match ($event->type) {
            'incident' => 'sor',
            'near_miss' => 'sor',
            'hazard' => 'sor',
            'violation' => 'sor',
            default => 'system',
        };

        // Notify assigned user
        if ($event->assigned_to) {
            Notification::create([
                'company_id' => $event->company_id,
                'user_id' => $event->assigned_to,
                'title' => "New {$event->type} assigned: {$event->title}",
                'message' => "A {$event->severity} severity {$event->type} has been assigned to you at {$event->location}.",
                'type' => $type,
                'urgency' => $urgency,
                'action_url' => "/sor?highlight={$event->id}",
                'action_text' => 'View Event',
                'dedupe_key' => "hse_event_assigned_{$event->id}",
                'data' => ['event_id' => $event->id, 'event_type' => $event->type, 'severity' => $event->severity],
            ]);
        }

        // Notify all safety managers for critical/high events
        if (in_array($event->severity, ['critical', 'high'])) {
            $managers = \App\Models\User::where('company_id', $event->company_id)
                ->whereHas('roles', fn($q) => $q->where('name', 'safety_manager'))
                ->where('id', '!=', $event->assigned_to)
                ->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'company_id' => $event->company_id,
                    'user_id' => $manager->id,
                    'title' => "Critical {$event->type}: {$event->title}",
                    'message' => "A {$event->severity} severity {$event->type} has been reported at {$event->location}. Immediate attention required.",
                    'type' => $type,
                    'urgency' => 'critical',
                    'action_url' => "/sor?highlight={$event->id}",
                    'action_text' => 'Review Event',
                    'dedupe_key' => "hse_event_critical_{$event->id}_{$manager->id}",
                    'data' => ['event_id' => $event->id, 'event_type' => $event->type],
                ]);
            }
        }
    }

    public function updated(HseEvent $event): void
    {
        // Notify when status changes to closed/verified
        if ($event->isDirty('status') && in_array($event->status, ['closed', 'verified'])) {
            if ($event->reported_by) {
                Notification::create([
                    'company_id' => $event->company_id,
                    'user_id' => $event->reported_by,
                    'title' => "Event {$event->reference} {$event->status}",
                    'message' => "Your reported event \"{$event->title}\" has been marked as {$event->status}.",
                    'type' => 'sor',
                    'urgency' => 'info',
                    'action_url' => "/sor?highlight={$event->id}",
                    'action_text' => 'View Event',
                    'dedupe_key' => "hse_event_status_{$event->id}_{$event->status}",
                    'data' => ['event_id' => $event->id, 'new_status' => $event->status],
                ]);
            }
        }

        // Notify when overdue (escalation_level changes)
        if ($event->isDirty('escalation_level') && $event->escalation_level > 0) {
            $managers = \App\Models\User::where('company_id', $event->company_id)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['safety_manager', 'project_manager']))
                ->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'company_id' => $event->company_id,
                    'user_id' => $manager->id,
                    'title' => "Overdue event escalated: {$event->title}",
                    'message' => "Event {$event->reference} is overdue and has been escalated to level {$event->escalation_level}.",
                    'type' => 'sor',
                    'urgency' => 'urgent',
                    'action_url' => "/sor?highlight={$event->id}",
                    'action_text' => 'Take Action',
                    'dedupe_key' => "hse_event_escalated_{$event->id}_{$event->escalation_level}",
                    'data' => ['event_id' => $event->id, 'escalation_level' => $event->escalation_level],
                ]);
            }
        }
    }
}

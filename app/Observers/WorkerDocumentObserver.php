<?php

namespace App\Observers;

use App\Models\WorkerDocument;
use App\Models\Notification;

class WorkerDocumentObserver
{
    public function created(WorkerDocument $document): void
    {
        // Notify worker's supervisor about new document
        if ($document->worker && $document->worker->project_id) {
            $supervisors = \App\Models\User::where('company_id', $document->company_id)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['project_manager', 'hr_manager']))
                ->get();

            foreach ($supervisors as $supervisor) {
                Notification::create([
                    'company_id' => $document->company_id,
                    'user_id' => $supervisor->id,
                    'title' => "New document for {$document->worker->full_name}",
                    'message' => "A {$document->type} document \"{$document->name}\" has been added for {$document->worker->full_name}.",
                    'type' => 'worker',
                    'urgency' => 'info',
                    'action_url' => "/workers?highlight={$document->worker_id}",
                    'action_text' => 'View Worker',
                    'dedupe_key' => "worker_doc_new_{$document->id}_{$supervisor->id}",
                    'data' => ['document_id' => $document->id, 'worker_id' => $document->worker_id],
                ]);
            }
        }
    }

    public function updated(WorkerDocument $document): void
    {
        // Notify when document is about to expire (status changes to expiring)
        if ($document->isDirty('status') && $document->status === 'expiring') {
            $recipients = \App\Models\User::where('company_id', $document->company_id)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['hr_manager', 'safety_manager', 'project_manager']))
                ->get();

            foreach ($recipients as $user) {
                Notification::create([
                    'company_id' => $document->company_id,
                    'user_id' => $user->id,
                    'title' => "Document expiring: {$document->name}",
                    'message' => "The {$document->type} document \"{$document->name}\" for worker expires on {$document->expiry_date?->format('Y-m-d')}. Renewal needed.",
                    'type' => 'worker',
                    'urgency' => 'warning',
                    'action_url' => "/workers?highlight={$document->worker_id}",
                    'action_text' => 'View Document',
                    'dedupe_key' => "worker_doc_expiring_{$document->id}_{$user->id}",
                    'data' => ['document_id' => $document->id, 'expiry_date' => $document->expiry_date?->toDateString()],
                ]);
            }
        }
    }
}

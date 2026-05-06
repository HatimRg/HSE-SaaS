<?php

namespace App\Observers;

use App\Models\EnvironmentalReading;
use App\Models\Notification;

class EnvironmentalReadingObserver
{
    public function created(EnvironmentalReading $reading): void
    {
        if (!$reading->is_exceedance) {
            return;
        }

        // Notify safety managers and environmental officers
        $recipients = \App\Models\User::where('company_id', $reading->company_id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['safety_manager', 'environmental_officer', 'project_manager']))
            ->get();

        foreach ($recipients as $user) {
            Notification::create([
                'company_id' => $reading->company_id,
                'user_id' => $user->id,
                'title' => "Environmental threshold exceeded: {$reading->type}",
                'message' => "Reading of {$reading->value} {$reading->unit} at {$reading->location} exceeds the threshold for {$reading->type}.",
                'type' => 'system',
                'urgency' => 'urgent',
                'action_url' => '/environment',
                'action_text' => 'View Reading',
                'dedupe_key' => "env_exceedance_{$reading->id}_{$user->id}",
                'data' => [
                    'reading_id' => $reading->id,
                    'type' => $reading->type,
                    'value' => $reading->value,
                    'threshold_max' => $reading->threshold_max,
                ],
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\HseEvent;
use App\Models\EventAction;
use Illuminate\Database\Seeder;

class HseEventSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $projectId = 1;

        $types = ['observation', 'near_miss', 'incident', 'hazard', 'violation', 'improvement'];
        $severities = ['low', 'medium', 'high', 'critical'];
        $statuses = ['open', 'in_progress', 'closed', 'verified'];

        $events = [
            ['type' => 'observation', 'severity' => 'low', 'title' => 'Wet floor near entrance B', 'location' => 'Building A - Entrance B', 'status' => 'closed'],
            ['type' => 'near_miss', 'severity' => 'medium', 'title' => 'Scaffolding component fell from height', 'location' => 'Tower 3 - Level 5', 'status' => 'in_progress'],
            ['type' => 'incident', 'severity' => 'high', 'title' => 'Worker slipped on oily surface', 'location' => 'Workshop Zone C', 'status' => 'closed'],
            ['type' => 'hazard', 'severity' => 'medium', 'title' => 'Exposed electrical wiring in corridor', 'location' => 'Building B - Ground Floor', 'status' => 'open'],
            ['type' => 'violation', 'severity' => 'high', 'title' => 'Working at height without harness', 'location' => 'Tower 2 - Roof', 'status' => 'in_progress'],
            ['type' => 'improvement', 'severity' => 'low', 'title' => 'Better signage needed for emergency exits', 'location' => 'Site-wide', 'status' => 'open'],
            ['type' => 'observation', 'severity' => 'medium', 'title' => 'PPE not worn in designated area', 'location' => 'Zone D - Loading Bay', 'status' => 'closed'],
            ['type' => 'near_miss', 'severity' => 'high', 'title' => 'Crane load swung near workers', 'location' => 'Tower 1 - Level 8', 'status' => 'verified'],
            ['type' => 'incident', 'severity' => 'critical', 'title' => 'Fall from scaffolding - injury reported', 'location' => 'Building C - Level 3', 'status' => 'closed'],
            ['type' => 'observation', 'severity' => 'low', 'title' => 'Missing guardrail on stairwell', 'location' => 'Building A - Stairwell 2', 'status' => 'in_progress'],
            ['type' => 'hazard', 'severity' => 'high', 'title' => 'Chemical spill containment breach', 'location' => 'Chemical Storage Area', 'status' => 'closed'],
            ['type' => 'violation', 'severity' => 'medium', 'title' => 'Unauthorized personnel in restricted zone', 'location' => 'Zone A - Restricted', 'status' => 'open'],
        ];

        foreach ($events as $i => $eventData) {
            $occurredAt = now()->subDays(rand(1, 60))->setHour(rand(7, 17))->setMinute(rand(0, 59));
            $dueDate = (clone $occurredAt)->addDays(rand(3, 14));

            $event = HseEvent::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'title' => $eventData['title'],
                ],
                [
                    'project_id' => $projectId,
                    'type' => $eventData['type'],
                    'severity' => $eventData['severity'],
                    'status' => $eventData['status'],
                    'description' => "Auto-generated {$eventData['type']} event for testing.",
                    'location' => $eventData['location'],
                    'occurred_at' => $occurredAt,
                    'due_date' => $dueDate,
                    'reported_by' => 2, // First non-admin user
                    'assigned_to' => rand(2, 4),
                    'reference' => 'HSE-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                ]
            );

            // Create corrective action for high/critical events
            if (in_array($eventData['severity'], ['high', 'critical']) && $event->wasRecentlyCreated) {
                EventAction::create([
                    'company_id' => $companyId,
                    'source_type' => HseEvent::class,
                    'source_id' => $event->id,
                    'type' => 'corrective',
                    'description' => "Investigate and resolve: {$eventData['title']}",
                    'priority' => $eventData['severity'] === 'critical' ? 'critical' : 'high',
                    'status' => $eventData['status'] === 'closed' ? 'completed' : 'in_progress',
                    'assigned_to' => rand(2, 4),
                    'due_date' => (clone $occurredAt)->addDays(7),
                    'completed_at' => $eventData['status'] === 'closed' ? now()->subDays(rand(1, 5)) : null,
                ]);
            }
        }

        $this->command->info('Seeded ' . count($events) . ' HSE events with actions.');
    }
}

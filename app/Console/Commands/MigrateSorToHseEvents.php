<?php

namespace App\Console\Commands;

use App\Models\HseEvent;
use App\Models\EventAction;
use App\Models\SorReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSorToHseEvents extends Command
{
    protected $signature = 'migrate:sor-to-hse-events
                            {--dry-run : Show what would be migrated without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Migrate existing sor_reports data into the unified hse_events table';

    public function handle(): int
    {
        $sorReports = SorReport::withTrashed()->get();

        if ($sorReports->isEmpty()) {
            $this->info('No SOR reports found to migrate.');
            return self::SUCCESS;
        }

        $this->info("Found {$sorReports->count()} SOR reports to migrate.");

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Reference', 'Title', 'Type', 'Severity', 'Status'],
                $sorReports->map(fn($s) => [$s->id, $s->reference, $s->title, $s->type, $s->severity, $s->status])
            );
            $this->info('Dry run complete. No changes made.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Proceed with migration?')) {
            $this->info('Migration cancelled.');
            return self::SUCCESS;
        }

        $migrated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($sorReports as $sor) {
                // Map SOR type to HSE event type
                $eventType = $this->mapType($sor->type);

                // Map SOR status to HSE event status
                $eventStatus = $this->mapStatus($sor->status);

                // Check if already migrated (by reference)
                $existing = HseEvent::where('reference', $sor->reference)->first();
                if ($existing) {
                    $skipped++;
                    continue;
                }

                $event = HseEvent::create([
                    'company_id' => $sor->company_id,
                    'project_id' => $sor->project_id,
                    'reference' => $sor->reference,
                    'type' => $eventType,
                    'severity' => $sor->severity,
                    'status' => $eventStatus,
                    'title' => $sor->title,
                    'description' => $sor->description,
                    'location' => $sor->location,
                    'occurred_at' => $sor->date ?? $sor->created_at,
                    'reported_by' => $sor->user_id,
                    'assigned_to' => $sor->responsible_person_id,
                    'due_date' => $sor->due_date,
                    'closed_at' => $sor->completed_at,
                    'photos' => $sor->photos,
                    'attachments' => $sor->attachments,
                ]);

                // Migrate corrective action as an EventAction
                if ($sor->corrective_action) {
                    EventAction::create([
                        'company_id' => $sor->company_id,
                        'source_type' => HseEvent::class,
                        'source_id' => $event->id,
                        'type' => 'corrective',
                        'description' => $sor->corrective_action,
                        'priority' => $sor->severity === 'critical' ? 'critical' : ($sor->severity === 'high' ? 'high' : 'medium'),
                        'status' => $sor->completed_at ? 'completed' : 'open',
                        'assigned_to' => $sor->responsible_person_id,
                        'due_date' => $sor->due_date,
                        'completed_at' => $sor->completed_at,
                    ]);
                }

                $migrated++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Migration failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info("Migration complete: {$migrated} migrated, {$skipped} skipped (already exist).");
        $this->info('You can now update your frontend to use /hse-events instead of /sor-reports.');
        return self::SUCCESS;
    }

    private function mapType(string $sorType): string
    {
        return match ($sorType) {
            'unsafe_condition', 'unsafe_act', 'observation' => 'observation',
            'near_miss' => 'near_miss',
            'incident', 'accident' => 'incident',
            'hazard' => 'hazard',
            'violation', 'non_compliance' => 'violation',
            'improvement', 'suggestion' => 'improvement',
            default => 'observation',
        };
    }

    private function mapStatus(string $sorStatus): string
    {
        return match ($sorStatus) {
            'open' => 'open',
            'in-progress', 'in_progress' => 'in_progress',
            'closed', 'resolved' => 'closed',
            'verified' => 'verified',
            'cancelled' => 'cancelled',
            default => 'open',
        };
    }
}

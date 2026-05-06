<?php

namespace App\Console\Commands;

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\HseEvent;
use App\Models\EventAction;
use App\Models\DailyHeadcount;
use App\Models\Inspection;
use App\Models\WorkPermit;
use App\Models\Worker;
use App\Models\TrainingParticipant;
use Illuminate\Console\Command;

class ComputeKpis extends Command
{
    protected $signature = 'kpi:compute
                            {--project= : Specific project ID}
                            {--period=month : Period type (week, month, quarter, year)}
                            {--company= : Specific company ID (super admin only)}';

    protected $description = 'Compute KPI values from safety data for all active definitions';

    public function handle(): int
    {
        $periodType = $this->option('period');
        $projectId = $this->option('project');
        $companyId = $this->option('company') ?? auth()->user()?->company_id ?? 1;

        $dateRange = $this->getDateRange($periodType);

        $definitions = KpiDefinition::where('is_active', true);
        if ($companyId) {
            $definitions->where('company_id', $companyId);
        }
        $definitions = $definitions->get();

        if ($definitions->isEmpty()) {
            $this->warn('No active KPI definitions found.');
            return self::SUCCESS;
        }

        $projects = $projectId
            ? [\App\Models\Project::findOrFail($projectId)]
            : \App\Models\Project::where('company_id', $companyId)->where('status', 'active')->get();

        $computed = 0;

        foreach ($projects as $project) {
            $this->info("Computing KPIs for project: {$project->name}");

            foreach ($definitions as $def) {
                $value = $this->computeKpi($def, $companyId, $project->id, $dateRange['start'], $dateRange['end']);

                if ($value !== null) {
                    $status = $this->determineStatus($value, $def);

                    KpiValue::updateOrCreate(
                        [
                            'kpi_definition_id' => $def->id,
                            'project_id' => $project->id,
                            'period_start' => $dateRange['start']->toDateString(),
                            'period_end' => $dateRange['end']->toDateString(),
                        ],
                        [
                            'company_id' => $companyId,
                            'value' => $value,
                            'target_value' => $def->target_value,
                            'status' => $status,
                            'input_snapshot' => $this->getInputSnapshot($def, $companyId, $project->id, $dateRange['start'], $dateRange['end']),
                            'computed_at' => now(),
                        ]
                    );

                    $this->line("  ✓ {$def->name}: {$value} ({$status})");
                    $computed++;
                }
            }
        }

        $this->info("Computed {$computed} KPI values.");
        return self::SUCCESS;
    }

    private function getDateRange(string $period): array
    {
        return match ($period) {
            'week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'quarter' => ['start' => now()->startOfQuarter(), 'end' => now()->endOfQuarter()],
            'year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
        };
    }

    private function computeKpi(KpiDefinition $def, int $companyId, int $projectId, $start, $end): ?float
    {
        return match ($def->code) {
            'trir' => $this->computeTRIR($companyId, $projectId, $start, $end),
            'ltifr' => $this->computeLTIFR($companyId, $projectId, $start, $end),
            'near_miss_rate' => $this->computeNearMissRate($companyId, $projectId, $start, $end),
            'action_closure_rate' => $this->computeActionClosureRate($companyId, $projectId, $start, $end),
            'inspection_compliance' => $this->computeInspectionCompliance($companyId, $projectId, $start, $end),
            'permit_compliance' => $this->computePermitCompliance($companyId, $projectId),
            'training_completion' => $this->computeTrainingCompletion($companyId, $projectId),
            default => null,
        };
    }

    private function computeTRIR(int $companyId, int $projectId, $start, $end): float
    {
        $incidents = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('type', 'incident')
            ->whereIn('severity', ['high', 'critical'])
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $hours = $this->getTotalHours($companyId, $projectId, $start, $end);
        return $hours > 0 ? round(($incidents * 200000) / $hours, 2) : 0;
    }

    private function computeLTIFR(int $companyId, int $projectId, $start, $end): float
    {
        $lti = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('type', 'incident')
            ->whereNotNull('closed_at')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $hours = $this->getTotalHours($companyId, $projectId, $start, $end);
        return $hours > 0 ? round(($lti * 1000000) / $hours, 2) : 0;
    }

    private function computeNearMissRate(int $companyId, int $projectId, $start, $end): float
    {
        $nearMisses = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('type', 'near_miss')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $total = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        return $total > 0 ? round(($nearMisses / $total) * 100, 1) : 0;
    }

    private function computeActionClosureRate(int $companyId, int $projectId, $start, $end): float
    {
        $total = EventAction::where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $closed = EventAction::where('company_id', $companyId)
            ->whereIn('status', ['completed', 'verified'])
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return $total > 0 ? round(($closed / $total) * 100, 1) : 0;
    }

    private function computeInspectionCompliance(int $companyId, int $projectId, $start, $end): float
    {
        $total = Inspection::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $passed = Inspection::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('result', 'pass')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return $total > 0 ? round(($passed / $total) * 100, 1) : 0;
    }

    private function computePermitCompliance(int $companyId, int $projectId): float
    {
        $total = WorkPermit::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->count();

        $active = WorkPermit::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->where('expiry_date', '>=', now())
            ->count();

        return $total > 0 ? round(($active / $total) * 100, 1) : 100;
    }

    private function computeTrainingCompletion(int $companyId, int $projectId): float
    {
        $totalWorkers = Worker::where('company_id', $companyId)->where('status', 'active')->count();
        $trained = TrainingParticipant::where('company_id', $companyId)
            ->where('status', 'attended')
            ->distinct('worker_id')
            ->count('worker_id');

        return $totalWorkers > 0 ? round(($trained / $totalWorkers) * 100, 1) : 0;
    }

    private function getTotalHours(int $companyId, int $projectId, $start, $end): float
    {
        return DailyHeadcount::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereBetween('date', [$start, $end])
            ->sum('total_count') * 8;
    }

    private function determineStatus(float $value, KpiDefinition $def): string
    {
        if ($def->target_value === null) return 'on_target';

        $isLowerBetter = $def->direction === 'lower_is_better';
        $ratio = $isLowerBetter
            ? $def->target_value / max($value, 0.001)
            : $value / max($def->target_value, 0.001);

        if ($ratio >= 1.0) return 'on_target';
        if ($ratio >= 0.7) return 'warning';
        return 'critical';
    }

    private function getInputSnapshot(KpiDefinition $def, int $companyId, int $projectId, $start, $end): array
    {
        return match ($def->code) {
            'trir' => [
                'recordable_incidents' => HseEvent::where('company_id', $companyId)->where('project_id', $projectId)->where('type', 'incident')->whereIn('severity', ['high', 'critical'])->whereBetween('occurred_at', [$start, $end])->count(),
                'total_hours' => $this->getTotalHours($companyId, $projectId, $start, $end),
            ],
            'ltifr' => [
                'lost_time_incidents' => HseEvent::where('company_id', $companyId)->where('project_id', $projectId)->where('type', 'incident')->whereNotNull('closed_at')->whereBetween('occurred_at', [$start, $end])->count(),
                'total_hours' => $this->getTotalHours($companyId, $projectId, $start, $end),
            ],
            default => [],
        };
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\HseEvent;
use App\Models\DailyHeadcount;
use App\Models\EventAction;
use App\Models\WorkPermit;
use App\Models\Inspection;
use App\Models\Worker;
use App\Models\TrainingParticipant;
use Illuminate\Http\Request;

class KpiEngineController extends BaseController
{
    public function definitions(Request $request)
    {
        $query = KpiDefinition::where('is_active', true)->orderBy('sort_order');

        return $this->successResponse($query->get());
    }

    public function values(Request $request)
    {
        $request->validate([
            'project_id' => 'sometimes|exists:projects,id',
            'kpi_code' => 'sometimes|string',
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date',
        ]);

        $query = KpiValue::with(['definition', 'project']);

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('kpi_code')) {
            $definition = KpiDefinition::where('code', $request->kpi_code)->first();
            if ($definition) {
                $query->where('kpi_definition_id', $definition->id);
            }
        }
        if ($request->has('period_start')) {
            $query->where('period_start', '>=', $request->period_start);
        }
        if ($request->has('period_end')) {
            $query->where('period_end', '<=', $request->period_end);
        }

        $query->orderBy('period_start', 'desc');

        return $this->paginatedResponse($query, $request, 'kpi_engine:values');
    }

    public function compute(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        $companyId = auth()->user()->company_id;
        $projectId = $request->project_id;
        $periodStart = $request->period_start;
        $periodEnd = $request->period_end;

        $definitions = KpiDefinition::where('company_id', $companyId)->where('is_active', true)->get();
        $computed = [];

        foreach ($definitions as $def) {
            $value = $this->computeKpi($def, $companyId, $projectId, $periodStart, $periodEnd);

            if ($value !== null) {
                $status = $this->determineStatus($value, $def);

                $kpiValue = KpiValue::updateOrCreate(
                    [
                        'kpi_definition_id' => $def->id,
                        'project_id' => $projectId,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ],
                    [
                        'company_id' => $companyId,
                        'value' => $value,
                        'target_value' => $def->target_value,
                        'status' => $status,
                        'input_snapshot' => $this->getInputSnapshot($def, $companyId, $projectId, $periodStart, $periodEnd),
                        'computed_at' => now(),
                    ]
                );

                $computed[] = $kpiValue->load('definition');
            }
        }

        $this->logActivity('kpi_computed', null, ['project_id' => $projectId, 'period' => "$periodStart to $periodEnd"]);

        return $this->successResponse($computed, 'KPIs computed successfully');
    }

    public function dashboard(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $projectId = $request->get('project_id');

        // Get latest KPI values for dashboard display
        $latestValues = KpiValue::where('company_id', $companyId)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->with('definition')
            ->selectRaw('kpi_values.*')
            ->joinSub(
                KpiValue::selectRaw('kpi_definition_id, project_id, MAX(period_end) as max_end')
                    ->where('company_id', $companyId)
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->groupBy('kpi_definition_id', 'project_id'),
                'latest',
                fn($join) => $join->on('kpi_values.kpi_definition_id', 'latest.kpi_definition_id')
                    ->on('kpi_values.project_id', 'latest.project_id')
                    ->on('kpi_values.period_end', 'latest.max_end')
            )
            ->get();

        return $this->successResponse($latestValues);
    }

    private function computeKpi(KpiDefinition $def, int $companyId, int $projectId, string $periodStart, string $periodEnd): ?float
    {
        $code = $def->code;

        return match($code) {
            'trir' => $this->computeTRIR($companyId, $projectId, $periodStart, $periodEnd),
            'ltifr' => $this->computeLTIFR($companyId, $projectId, $periodStart, $periodEnd),
            'near_miss_rate' => $this->computeNearMissRate($companyId, $projectId, $periodStart, $periodEnd),
            'action_closure_rate' => $this->computeActionClosureRate($companyId, $projectId, $periodStart, $periodEnd),
            'inspection_compliance' => $this->computeInspectionCompliance($companyId, $projectId, $periodStart, $periodEnd),
            'permit_compliance' => $this->computePermitCompliance($companyId, $projectId),
            'training_completion' => $this->computeTrainingCompletion($companyId, $projectId),
            default => null,
        };
    }

    private function computeTRIR(int $companyId, int $projectId, string $start, string $end): float
    {
        $recordableIncidents = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('type', 'incident')
            ->whereIn('severity', ['high', 'critical'])
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $totalHours = $this->getTotalHours($companyId, $projectId, $start, $end);

        return $totalHours > 0 ? round(($recordableIncidents * 200000) / $totalHours, 2) : 0;
    }

    private function computeLTIFR(int $companyId, int $projectId, string $start, string $end): float
    {
        $lostTimeIncidents = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('type', 'incident')
            ->whereNotNull('closed_at')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $totalHours = $this->getTotalHours($companyId, $projectId, $start, $end);

        return $totalHours > 0 ? round(($lostTimeIncidents * 1000000) / $totalHours, 2) : 0;
    }

    private function computeNearMissRate(int $companyId, int $projectId, string $start, string $end): float
    {
        $nearMisses = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->where('type', 'near_miss')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $totalEvents = HseEvent::where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        return $totalEvents > 0 ? round(($nearMisses / $totalEvents) * 100, 1) : 0;
    }

    private function computeActionClosureRate(int $companyId, int $projectId, string $start, string $end): float
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

    private function computeInspectionCompliance(int $companyId, int $projectId, string $start, string $end): float
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

    private function getTotalHours(int $companyId, int $projectId, string $start, string $end): float
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
        $ratio = $isLowerBetter ? $def->target_value / max($value, 0.001) : $value / max($def->target_value, 0.001);

        if ($ratio >= 1.0) return 'on_target';
        if ($ratio >= 0.7) return 'warning';
        return 'critical';
    }

    private function getInputSnapshot(KpiDefinition $def, int $companyId, int $projectId, string $start, string $end): array
    {
        return match($def->code) {
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

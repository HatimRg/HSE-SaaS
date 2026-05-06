<?php

namespace App\Http\Controllers\Api;

use App\Models\EnvironmentalReading;
use App\Models\WasteExport;
use Illuminate\Http\Request;

class EnvironmentController extends BaseController
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $projectId = $request->get('project_id');

        $readingQuery = EnvironmentalReading::where('company_id', $companyId);
        if ($projectId) {
            $readingQuery->where('project_id', $projectId);
        }

        $latestReadings = $readingQuery->orderBy('measured_at', 'desc')->get()->groupBy('type')->map(fn($items) => $items->first());

        $metrics = [
            'air_quality' => $latestReadings->get('air_quality_aqi')?->value ?? 0,
            'noise_level' => $latestReadings->get('noise')?->value ?? 0,
            'waste_diversion' => $this->calculateWasteDiversion($companyId, $projectId),
            'water_usage' => $latestReadings->get('water_consumption')?->value ?? 0,
            'temperature' => $latestReadings->get('temperature')?->value ?? 0,
            'emissions' => $this->calculateEmissions($companyId, $projectId),
        ];

        return $this->successResponse($metrics);
    }

    public function readings(Request $request)
    {
        $query = EnvironmentalReading::with(['project', 'measuredBy']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('from')) {
            $query->where('measured_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('measured_at', '<=', $request->to);
        }
        if ($request->has('is_exceedance')) {
            $query->where('is_exceedance', filter_var($request->is_exceedance, FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('measured_at', 'desc');

        return $this->paginatedResponse($query, $request, 'environment:readings');
    }

    public function storeReading(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:noise,dust_pm10,dust_pm25,water_ph,water_turbidity,air_quality_aqi,vibration,temperature,humidity,electricity_kwh,water_consumption',
            'value' => 'required|numeric',
            'unit' => 'required|string|max:20',
            'threshold_min' => 'nullable|numeric',
            'threshold_max' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
            'measured_at' => 'required|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Auto-detect exceedance
        $isExceedance = false;
        if (isset($validated['threshold_max']) && $validated['value'] > $validated['threshold_max']) {
            $isExceedance = true;
        }
        if (isset($validated['threshold_min']) && $validated['value'] < $validated['threshold_min']) {
            $isExceedance = true;
        }
        $validated['is_exceedance'] = $isExceedance;
        $validated['measured_by'] = auth()->id();

        $reading = EnvironmentalReading::create($validated);
        $this->logActivity('created', $reading);

        return $this->successResponse($reading->load(['project', 'measuredBy']), 'Reading recorded successfully', 201);
    }

    public function wasteExports(Request $request)
    {
        $query = WasteExport::with(['project', 'recorder']);

        if ($request->has('waste_type')) {
            $query->where('waste_type', $request->waste_type);
        }
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('is_hazardous')) {
            $query->where('is_hazardous', filter_var($request->is_hazardous, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->has('from')) {
            $query->where('date', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('date', '<=', $request->to);
        }

        $query->orderBy('date', 'desc');

        return $this->paginatedResponse($query, $request, 'environment:waste');
    }

    public function storeWasteExport(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'waste_type' => 'required|in:construction_debris,hazardous,metal,concrete,wood,plastic,chemical,asbestos,general,other',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'sometimes|string|max:20',
            'transport_method' => 'sometimes|in:truck,skip,pipeline,other',
            'treatment_facility' => 'nullable|string|max:255',
            'treatment' => 'nullable|in:recycling,landfill,incineration,reuse,other',
            'is_hazardous' => 'boolean',
            'carrier_name' => 'nullable|string|max:255',
            'manifest_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['recorded_by'] = auth()->id();

        $waste = WasteExport::create($validated);
        $this->logActivity('created', $waste);

        return $this->successResponse($waste->load(['project', 'recorder']), 'Waste export recorded successfully', 201);
    }

    public function charts(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $projectId = $request->get('project_id');
        $range = $request->get('range', '30d');

        $days = match($range) {
            '7d' => 7, '30d' => 30, '90d' => 90, '1y' => 365, default => 30,
        };

        $from = now()->subDays($days);

        // Readings trend by type
        $readingsQuery = EnvironmentalReading::where('company_id', $companyId)
            ->where('measured_at', '>=', $from);
        if ($projectId) {
            $readingsQuery->where('project_id', $projectId);
        }

        $readingsTrend = $readingsQuery
            ->selectRaw("DATE(measured_at) as date, type, AVG(value) as avg_value")
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('type')
            ->map(fn($items, $type) => [
                'type' => $type,
                'data' => $items->map(fn($i) => ['date' => $i->date, 'value' => round($i->avg_value, 2)]),
            ])->values();

        // Exceedance summary
        $exceedances = EnvironmentalReading::where('company_id', $companyId)
            ->where('measured_at', '>=', $from)
            ->where('is_exceedance', true)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();

        // Waste breakdown
        $wasteBreakdown = WasteExport::where('company_id', $companyId)
            ->where('date', '>=', $from)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->selectRaw('waste_type, treatment, SUM(quantity) as total')
            ->groupBy('waste_type', 'treatment')
            ->get();

        return $this->successResponse([
            'readings_trend' => $readingsTrend,
            'exceedances' => $exceedances,
            'waste_breakdown' => $wasteBreakdown,
        ]);
    }

    private function calculateWasteDiversion(int $companyId, ?int $projectId): float
    {
        $query = WasteExport::where('company_id', $companyId);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $total = (clone $query)->sum('quantity');
        $recycled = (clone $query)->where('treatment', 'recycling')->sum('quantity');

        return $total > 0 ? round(($recycled / $total) * 100, 1) : 0;
    }

    private function calculateEmissions(int $companyId, ?int $projectId): float
    {
        return EnvironmentalReading::where('company_id', $companyId)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->where('type', 'air_quality_aqi')
            ->orderByDesc('measured_at')
            ->value('value') ?? 0;
    }
}

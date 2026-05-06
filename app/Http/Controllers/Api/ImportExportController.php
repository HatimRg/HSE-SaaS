<?php

namespace App\Http\Controllers\Api;

use App\Models\Worker;
use App\Models\TrainingSession;
use App\Models\KpiValue;
use App\Models\KpiDefinition;
use App\Models\HseEvent;
use App\Models\EventAction;
use App\Models\EnvironmentalReading;
use App\Models\WasteExport;
use App\Models\RiskAssessment;
use App\Models\WorkerDocument;
use App\Models\WorkPermit;
use App\Models\Inspection;
use App\Models\PpeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Barryvdh\DomPDF\Facade\Pdf;

class ImportExportController extends BaseController
{
    /**
     * Import workers from Excel file.
     */
    public function importWorkers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'project_id' => 'required|exists:projects,id',
        ]);

        $file = $request->file('file');
        $projectId = $request->project_id;

        try {
            $import = new WorkersImport($projectId);
            Excel::import($import, $file);

            $results = $import->getResults();

            return $this->successResponse([
                'success_count' => $results['success_count'],
                'failed_count' => $results['failed_count'],
                'failed_rows' => $results['failed_rows'],
                'total_processed' => $results['total_processed'],
            ], 'Worker import completed');

        } catch (\Exception $e) {
            return $this->errorResponse('Import failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export workers to Excel.
     */
    public function exportWorkers(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $workers = Worker::when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->get();

        $fileName = 'workers_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new WorkersExport($workers), $fileName);
    }

    /**
     * Import training records from Excel.
     */
    public function importTraining(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'project_id' => 'required|exists:projects,id',
        ]);

        try {
            $import = new TrainingImport($request->project_id);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            return $this->successResponse($results, 'Training import completed');

        } catch (\Exception $e) {
            return $this->errorResponse('Import failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export training records to Excel.
     */
    public function exportTraining(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $training = TrainingSession::when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->with(['trainer', 'workers'])->get();

        $fileName = 'training_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new TrainingExport($training), $fileName);
    }

    /**
     * Import KPI reports from Excel.
     */
    public function importKpiReports(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'project_id' => 'required|exists:projects,id',
        ]);

        try {
            $import = new KpiImport($request->project_id);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            return $this->successResponse($results, 'KPI import completed');

        } catch (\Exception $e) {
            return $this->errorResponse('Import failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export KPI reports to Excel.
     */
    public function exportKpiReports(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = KpiValue::with('definition')->when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->when($dateFrom, function ($query, $dateFrom) {
            $query->whereDate('period_start', '>=', $dateFrom);
        })->when($dateTo, function ($query, $dateTo) {
            $query->whereDate('period_end', '<=', $dateTo);
        });

        $values = $query->get();

        $fileName = 'kpi_values_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($values) implements FromCollection, WithHeadings {
            private $values;
            public function __construct($values) { $this->values = $values; }
            public function collection() { return $this->values->map(fn($v) => [
                'definition' => $v->definition?->name,
                'project_id' => $v->project_id,
                'period_start' => $v->period_start,
                'period_end' => $v->period_end,
                'value' => $v->value,
                'target' => $v->target_value,
                'status' => $v->status,
                'computed_at' => $v->computed_at,
            ]); }
            public function headings(): array { return ['Definition', 'Project ID', 'Period Start', 'Period End', 'Value', 'Target', 'Status', 'Computed At']; }
        }, $fileName);
    }

    /**
     * Export HSE events to Excel.
     */
    public function exportHseEvents(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = HseEvent::with(['project', 'reporter', 'assignee'])->when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->when($dateFrom, function ($query, $dateFrom) {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        })->when($dateTo, function ($query, $dateTo) {
            $query->whereDate('occurred_at', '<=', $dateTo);
        });

        $events = $query->get();

        $fileName = 'hse_events_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($events) implements FromCollection, WithHeadings {
            private $events;
            public function __construct($events) { $this->events = $events; }
            public function collection() { return $this->events->map(fn($e) => [
                'reference' => $e->reference,
                'type' => $e->type,
                'severity' => $e->severity,
                'status' => $e->status,
                'title' => $e->title,
                'location' => $e->location,
                'occurred_at' => $e->occurred_at,
                'project' => $e->project?->name,
                'reporter' => $e->reporter?->name,
                'assignee' => $e->assignee?->name,
            ]); }
            public function headings(): array { return ['Reference', 'Type', 'Severity', 'Status', 'Title', 'Location', 'Occurred At', 'Project', 'Reporter', 'Assignee']; }
        }, $fileName);
    }

    /**
     * Generate weekly HSE summary PDF.
     */
    public function generateWeeklySummary(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'week_start' => 'required|date',
        ]);

        $projectId = $request->project_id;
        $weekStart = $request->week_start;
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        // Gather data for the week
        $data = [
            'project' => \App\Models\Project::find($projectId),
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'kpi_values' => KpiValue::with('definition')->where('project_id', $projectId)
                ->whereBetween('computed_at', [$weekStart, $weekEnd])
                ->get(),
            'hse_events' => HseEvent::where('project_id', $projectId)
                ->whereBetween('occurred_at', [$weekStart, $weekEnd])
                ->get(),
            'work_permits' => WorkPermit::where('project_id', $projectId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get(),
            'inspections' => Inspection::where('project_id', $projectId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get(),
            'training_sessions' => TrainingSession::where('project_id', $projectId)
                ->whereBetween('scheduled_date', [$weekStart, $weekEnd])
                ->get(),
        ];

        $pdf = PDF::loadView('exports.weekly-hse-summary', $data);
        
        $fileName = 'weekly_hse_summary_' . $projectId . '_' . $weekStart . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Export failed import rows to Excel.
     */
    public function exportFailedRows(Request $request)
    {
        $request->validate([
            'failed_rows' => 'required|array',
            'failed_rows.*.row_number' => 'required|integer',
            'failed_rows.*.data' => 'required|array',
            'failed_rows.*.errors' => 'required|array',
        ]);

        $failedRows = collect($request->failed_rows);
        $fileName = 'failed_import_rows_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new FailedRowsExport($failedRows), $fileName);
    }

    /**
     * Get import templates.
     */
    public function getImportTemplates()
    {
        $templates = [
            'workers' => [
                'name' => 'Workers Import Template',
                'description' => 'Template for importing worker data',
                'columns' => [
                    'first_name' => 'First Name (required)',
                    'last_name' => 'Last Name (required)',
                    'email' => 'Email (optional)',
                    'phone' => 'Phone (optional)',
                    'cin' => 'CIN (National ID, required)',
                    'job_function' => 'Job Function (required)',
                    'hire_date' => 'Hire Date (YYYY-MM-DD)',
                    'medical_fitness_date' => 'Medical Fitness Date (YYYY-MM-DD)',
                    'medical_fitness_expiry' => 'Medical Fitness Expiry (YYYY-MM-DD)',
                ],
                'download_url' => route('import-template', 'workers'),
            ],
            'training' => [
                'name' => 'Training Import Template',
                'description' => 'Template for importing training records',
                'columns' => [
                    'title' => 'Training Title (required)',
                    'description' => 'Description (optional)',
                    'training_type' => 'Type (safety/technical/environmental/administrative)',
                    'scheduled_date' => 'Scheduled Date (YYYY-MM-DD HH:MM)',
                    'duration_hours' => 'Duration in Hours',
                    'location' => 'Location (required)',
                    'trainer_email' => 'Trainer Email',
                    'max_participants' => 'Max Participants',
                ],
                'download_url' => route('import-template', 'training'),
            ],
            'kpi' => [
                'name' => 'KPI Import Template',
                'description' => 'Template for importing KPI reports',
                'columns' => [
                    'report_date' => 'Report Date (YYYY-MM-DD)',
                    'trir' => 'TRIR value',
                    'ltifr' => 'LTIFR value',
                    'severity_rate' => 'Severity Rate',
                    'near_miss_rate' => 'Near Miss Rate',
                    'total_hours_worked' => 'Total Hours Worked',
                    'daily_headcount' => 'Daily Headcount',
                    'incidents_count' => 'Number of Incidents',
                    'near_misses_count' => 'Number of Near Misses',
                ],
                'download_url' => route('import-template', 'kpi'),
            ],
        ];

        return $this->successResponse($templates);
    }

    /**
     * Download import template.
     */
    public function downloadTemplate($type)
    {
        $templates = [
            'workers' => WorkersTemplate::class,
            'training' => TrainingTemplate::class,
            'kpi' => KpiTemplate::class,
        ];

        if (!isset($templates[$type])) {
            return $this->errorResponse('Template not found', 404);
        }

        $templateClass = $templates[$type];
        $fileName = $type . '_import_template.xlsx';

        return Excel::download(new $templateClass(), $fileName);
    }

    /**
     * Export event actions to Excel.
     */
    public function exportEventActions(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = EventAction::with(['assignee', 'verifier'])->when($projectId, function ($query, $projectId) {
            $query->whereHas('source', fn($q) => $q->where('project_id', $projectId));
        })->when($dateFrom, function ($query, $dateFrom) {
            $query->whereDate('due_date', '>=', $dateFrom);
        })->when($dateTo, function ($query, $dateTo) {
            $query->whereDate('due_date', '<=', $dateTo);
        });

        $actions = $query->get();

        $fileName = 'event_actions_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($actions) implements FromCollection, WithHeadings {
            private $actions;
            public function __construct($actions) { $this->actions = $actions; }
            public function collection() { return $this->actions->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'priority' => $a->priority,
                'status' => $a->status,
                'description' => $a->description,
                'due_date' => $a->due_date?->format('Y-m-d'),
                'completed_at' => $a->completed_at?->format('Y-m-d H:i:s'),
                'assignee' => $a->assignee?->name,
                'verifier' => $a->verifier?->name,
            ]); }
            public function headings(): array { return ['ID', 'Type', 'Priority', 'Status', 'Description', 'Due Date', 'Completed At', 'Assignee', 'Verifier']; }
        }, $fileName);
    }

    /**
     * Export environmental readings to Excel.
     */
    public function exportEnvironmentalReadings(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = EnvironmentalReading::with(['project', 'measuredBy'])->when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->when($dateFrom, function ($query, $dateFrom) {
            $query->whereDate('measured_at', '>=', $dateFrom);
        })->when($dateTo, function ($query, $dateTo) {
            $query->whereDate('measured_at', '<=', $dateTo);
        });

        $readings = $query->get();

        $fileName = 'environmental_readings_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($readings) implements FromCollection, WithHeadings {
            private $readings;
            public function __construct($readings) { $this->readings = $readings; }
            public function collection() { return $this->readings->map(fn($r) => [
                'type' => $r->type,
                'value' => $r->value,
                'unit' => $r->unit,
                'location' => $r->location,
                'is_exceedance' => $r->is_exceedance ? 'Yes' : 'No',
                'measured_at' => $r->measured_at?->format('Y-m-d H:i:s'),
                'project' => $r->project?->name,
                'measured_by' => $r->measuredBy?->name,
            ]); }
            public function headings(): array { return ['Type', 'Value', 'Unit', 'Location', 'Exceedance', 'Measured At', 'Project', 'Measured By']; }
        }, $fileName);
    }

    /**
     * Export waste exports to Excel.
     */
    public function exportWasteExports(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = WasteExport::with(['project', 'recorder'])->when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->when($dateFrom, function ($query, $dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        })->when($dateTo, function ($query, $dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        });

        $exports = $query->get();

        $fileName = 'waste_exports_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($exports) implements FromCollection, WithHeadings {
            private $exports;
            public function __construct($exports) { $this->exports = $exports; }
            public function collection() { return $this->exports->map(fn($e) => [
                'date' => $e->date?->format('Y-m-d'),
                'waste_type' => $e->waste_type,
                'quantity' => $e->quantity,
                'unit' => $e->unit,
                'treatment' => $e->treatment,
                'is_hazardous' => $e->is_hazardous ? 'Yes' : 'No',
                'carrier_name' => $e->carrier_name,
                'manifest_number' => $e->manifest_number,
                'project' => $e->project?->name,
            ]); }
            public function headings(): array { return ['Date', 'Waste Type', 'Quantity', 'Unit', 'Treatment', 'Hazardous', 'Carrier', 'Manifest', 'Project']; }
        }, $fileName);
    }

    /**
     * Export risk assessments to Excel.
     */
    public function exportRiskAssessments(Request $request)
    {
        $projectId = $request->project_id ?? null;

        $query = RiskAssessment::with(['project', 'assessor'])->when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        });

        $assessments = $query->get();

        $fileName = 'risk_assessments_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($assessments) implements FromCollection, WithHeadings {
            private $assessments;
            public function __construct($assessments) { $this->assessments = $assessments; }
            public function collection() { return $this->assessments->map(fn($a) => [
                'title' => $a->title,
                'category' => $a->category,
                'status' => $a->status,
                'risk_level' => $a->risk_level,
                'assessment_date' => $a->assessment_date?->format('Y-m-d'),
                'next_review_date' => $a->next_review_date?->format('Y-m-d'),
                'project' => $a->project?->name,
                'assessor' => $a->assessor?->name,
            ]); }
            public function headings(): array { return ['Title', 'Category', 'Status', 'Risk Level', 'Assessment Date', 'Next Review', 'Project', 'Assessor']; }
        }, $fileName);
    }

    /**
     * Export worker documents to Excel.
     */
    public function exportWorkerDocuments(Request $request)
    {
        $workerId = $request->worker_id ?? null;
        $projectId = $request->project_id ?? null;

        $query = WorkerDocument::with(['worker'])->when($workerId, function ($query, $workerId) {
            $query->where('worker_id', $workerId);
        })->when($projectId, function ($query, $projectId) {
            $query->whereHas('worker', fn($q) => $q->where('project_id', $projectId));
        });

        $documents = $query->get();

        $fileName = 'worker_documents_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new class($documents) implements FromCollection, WithHeadings {
            private $documents;
            public function __construct($documents) { $this->documents = $documents; }
            public function collection() { return $this->documents->map(fn($d) => [
                'type' => $d->type,
                'name' => $d->name,
                'worker' => $d->worker?->full_name,
                'issuer' => $d->issuer,
                'issue_date' => $d->issue_date?->format('Y-m-d'),
                'expiry_date' => $d->expiry_date?->format('Y-m-d'),
                'status' => $d->status,
            ]); }
            public function headings(): array { return ['Type', 'Name', 'Worker', 'Issuer', 'Issue Date', 'Expiry Date', 'Status']; }
        }, $fileName);
    }

    /**
     * Generate HSE events PDF report.
     */
    public function generateHseEventsPdf(Request $request)
    {
        $projectId = $request->project_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = HseEvent::with(['project', 'reporter', 'assignee'])->when($projectId, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->when($dateFrom, function ($query, $dateFrom) {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        })->when($dateTo, function ($query, $dateTo) {
            $query->whereDate('occurred_at', '<=', $dateTo);
        });

        $events = $query->orderBy('occurred_at', 'desc')->get();
        $project = $projectId ? \App\Models\Project::find($projectId) : null;

        $pdf = Pdf::loadView('exports.hse-events-pdf', [
            'events' => $events,
            'project' => $project,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generatedAt' => now(),
        ]);

        $fileName = 'hse_events_report_' . date('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Generate risk assessment PDF report.
     */
    public function generateRiskAssessmentPdf(Request $request, $id)
    {
        $assessment = RiskAssessment::with(['project', 'assessor', 'items.hazard'])->findOrFail($id);

        $pdf = Pdf::loadView('exports.risk-assessment-pdf', [
            'assessment' => $assessment,
            'generatedAt' => now(),
        ]);

        $fileName = 'risk_assessment_' . $assessment->id . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }
}

// Import Classes

class WorkersImport implements ToModel, WithHeadingRow
{
    protected $projectId;
    protected $results = [
        'success_count' => 0,
        'failed_count' => 0,
        'failed_rows' => [],
        'total_processed' => 0,
    ];

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    public function model(array $row)
    {
        $this->results['total_processed']++;

        $errors = [];

        // Validate required fields
        if (empty($row['first_name'])) $errors[] = 'First name is required';
        if (empty($row['last_name'])) $errors[] = 'Last name is required';
        if (empty($row['cin'])) $errors[] = 'CIN is required';
        if (empty($row['job_function'])) $errors[] = 'Job function is required';

        // Check for duplicate CIN
        if (!empty($row['cin']) && Worker::where('cin', $row['cin'])->exists()) {
            $errors[] = 'Worker with this CIN already exists';
        }

        if (!empty($errors)) {
            $this->results['failed_count']++;
            $this->results['failed_rows'][] = [
                'row_number' => $this->results['total_processed'],
                'data' => $row,
                'errors' => $errors,
            ];
            return null;
        }

        try {
            $worker = Worker::create([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'cin' => $row['cin'],
                'job_function' => $row['job_function'],
                'project_id' => $this->projectId,
                'hire_date' => !empty($row['hire_date']) ? $row['hire_date'] : null,
                'medical_fitness_date' => !empty($row['medical_fitness_date']) ? $row['medical_fitness_date'] : null,
                'medical_fitness_expiry' => !empty($row['medical_fitness_expiry']) ? $row['medical_fitness_expiry'] : null,
            ]);

            $this->results['success_count']++;
            return $worker;

        } catch (\Exception $e) {
            $this->results['failed_count']++;
            $this->results['failed_rows'][] = [
                'row_number' => $this->results['total_processed'],
                'data' => $row,
                'errors' => ['Database error: ' . $e->getMessage()],
            ];
            return null;
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}

// Export Classes

class WorkersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $workers;

    public function __construct($workers)
    {
        $this->workers = $workers;
    }

    public function collection()
    {
        return $this->workers;
    }

    public function headings(): array
    {
        return [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'CIN',
            'Job Function',
            'Project',
            'Hire Date',
            'Medical Fitness Date',
            'Medical Fitness Expiry',
            'Status',
            'Created At',
        ];
    }

    public function map($worker): array
    {
        return [
            $worker->id,
            $worker->first_name,
            $worker->last_name,
            $worker->email,
            $worker->phone,
            $worker->cin,
            $worker->job_function,
            $worker->project->name ?? 'N/A',
            $worker->hire_date?->format('Y-m-d'),
            $worker->medical_fitness_date?->format('Y-m-d'),
            $worker->medical_fitness_expiry?->format('Y-m-d'),
            $worker->status,
            $worker->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

class FailedRowsExport implements FromCollection, WithHeadings
{
    protected $failedRows;

    public function __construct($failedRows)
    {
        $this->failedRows = $failedRows;
    }

    public function collection()
    {
        return $this->failedRows;
    }

    public function headings(): array
    {
        return [
            'Row Number',
            'Errors',
            'Original Data',
        ];
    }

    public function map($row): array
    {
        return [
            $row['row_number'],
            implode(', ', $row['errors']),
            json_encode($row['data']),
        ];
    }
}

// Template Classes

class WorkersTemplate implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'cin',
            'job_function',
            'hire_date',
            'medical_fitness_date',
            'medical_fitness_expiry',
        ];
    }
}

class TrainingTemplate implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'title',
            'description',
            'training_type',
            'scheduled_date',
            'duration_hours',
            'location',
            'trainer_email',
            'max_participants',
        ];
    }
}

class KpiTemplate implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'report_date',
            'trir',
            'ltifr',
            'severity_rate',
            'near_miss_rate',
            'total_hours_worked',
            'daily_headcount',
            'incidents_count',
            'near_misses_count',
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\TrainingSession;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TrainingController extends BaseController
{
    /**
     * Display a listing of training sessions.
     */
    public function index(Request $request)
    {
        $sessions = TrainingSession::with(['trainer', 'workers', 'project'])
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('scheduled_date', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('scheduled_date', '<=', $date);
            })
            ->orderBy('scheduled_date', 'desc')
            ->paginate(15);

        return $this->successResponse($sessions);
    }

    /**
     * Store a newly created training session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'training_type' => 'required|in:safety,technical,environmental,administrative',
            'scheduled_date' => 'required|date',
            'duration_hours' => 'required|numeric|min:0.5|max:24',
            'location' => 'required|string|max:255',
            'trainer_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'max_participants' => 'nullable|integer|min:1',
            'certificate_required' => 'boolean',
            'materials' => 'nullable|array',
            'materials.*.name' => 'required|string|max:255',
            'materials.*.file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx|max:10240',
        ]);

        $session = TrainingSession::create($validated);

        // Handle file uploads
        if ($request->hasFile('materials')) {
            foreach ($request->file('materials') as $index => $file) {
                $path = $file->store('training-materials', 'public');
                $session->materials()->create([
                    'name' => $validated['materials'][$index]['name'],
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $session->load(['trainer', 'project']);

        $this->logActivity('training_created', $session, $validated);

        return $this->successResponse($session, 'Training session created successfully');
    }

    /**
     * Display the specified training session.
     */
    public function show(TrainingSession $training)
    {
        $training->load(['trainer', 'workers', 'project', 'materials']);

        return $this->successResponse($training);
    }

    /**
     * Update the specified training session.
     */
    public function update(Request $request, TrainingSession $training)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'training_type' => 'sometimes|in:safety,technical,environmental,administrative',
            'scheduled_date' => 'sometimes|date',
            'duration_hours' => 'sometimes|numeric|min:0.5|max:24',
            'location' => 'sometimes|string|max:255',
            'trainer_id' => 'sometimes|exists:users,id',
            'project_id' => 'sometimes|exists:projects,id',
            'max_participants' => 'nullable|integer|min:1',
            'certificate_required' => 'boolean',
            'status' => ['sometimes', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after:actual_start_time',
            'notes' => 'nullable|string',
        ]);

        $training->update($validated);

        $training->load(['trainer', 'workers', 'project']);

        $this->logActivity('training_updated', $training, $validated);

        return $this->successResponse($training, 'Training session updated successfully');
    }

    /**
     * Remove the specified training session.
     */
    public function destroy(TrainingSession $training)
    {
        // Delete associated files
        foreach ($training->materials as $material) {
            Storage::disk('public')->delete($material->file_path);
        }

        $training->delete();

        $this->logActivity('training_deleted', $training);

        return $this->successResponse(null, 'Training session deleted successfully');
    }

    /**
     * Add participants to training session.
     */
    public function addParticipants(Request $request, TrainingSession $training)
    {
        $validated = $request->validate([
            'worker_ids' => 'required|array',
            'worker_ids.*' => 'exists:workers,id',
        ]);

        // Check capacity
        if ($training->max_participants && 
            $training->workers()->count() + count($validated['worker_ids']) > $training->max_participants) {
            return $this->errorResponse('Training session capacity exceeded', 400);
        }

        $training->workers()->attach($validated['worker_ids']);

        $this->logActivity('training_participants_added', $training, $validated);

        return $this->successResponse(null, 'Participants added successfully');
    }

    /**
     * Remove participants from training session.
     */
    public function removeParticipants(Request $request, TrainingSession $training)
    {
        $validated = $request->validate([
            'worker_ids' => 'required|array',
            'worker_ids.*' => 'exists:workers,id',
        ]);

        $training->workers()->detach($validated['worker_ids']);

        $this->logActivity('training_participants_removed', $training, $validated);

        return $this->successResponse(null, 'Participants removed successfully');
    }

    /**
     * Mark attendance for training session.
     */
    public function markAttendance(Request $request, TrainingSession $training)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.worker_id' => 'required|exists:workers,id',
            'attendances.*.attended' => 'required|boolean',
            'attendances.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['attendances'] as $attendance) {
            $training->workers()->updateExistingPivot($attendance['worker_id'], [
                'attended' => $attendance['attended'],
                'attendance_notes' => $attendance['notes'] ?? null,
            ]);
        }

        $this->logActivity('training_attendance_marked', $training, $validated);

        return $this->successResponse(null, 'Attendance marked successfully');
    }

    /**
     * Generate certificates for completed training.
     */
    public function generateCertificates(TrainingSession $training)
    {
        if ($training->status !== 'completed') {
            return $this->errorResponse('Training must be completed to generate certificates', 400);
        }

        $attendedWorkers = $training->workers()->wherePivot('attended', true)->get();

        $certificates = [];
        foreach ($attendedWorkers as $worker) {
            $certificate = [
                'worker_name' => $worker->full_name,
                'training_title' => $training->title,
                'training_date' => $training->scheduled_date->format('d/m/Y'),
                'duration_hours' => $training->duration_hours,
                'trainer_name' => $training->trainer->name,
                'certificate_id' => 'CERT-' . $training->id . '-' . $worker->id,
                'generated_at' => now()->format('d/m/Y H:i'),
            ];

            $certificates[] = $certificate;

            // Store certificate record
            $worker->certificates()->create([
                'training_session_id' => $training->id,
                'certificate_data' => $certificate,
            ]);
        }

        $this->logActivity('training_certificates_generated', $training, ['count' => count($certificates)]);

        return $this->successResponse($certificates, 'Certificates generated successfully');
    }

    /**
     * Get training statistics.
     */
    public function statistics(Request $request)
    {
        $query = TrainingSession::query();

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $stats = [
            'total_sessions' => $query->count(),
            'completed_sessions' => $query->where('status', 'completed')->count(),
            'upcoming_sessions' => $query->where('status', 'scheduled')->where('scheduled_date', '>', now())->count(),
            'total_participants' => $query->withCount('workers')->get()->sum('workers_count'),
            'by_type' => $query->selectRaw('training_type, COUNT(*) as count')
                ->groupBy('training_type')
                ->pluck('count', 'training_type'),
            'by_month' => $query->selectRaw('YEAR(scheduled_date) as year, MONTH(scheduled_date) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get(),
        ];

        return $this->successResponse($stats);
    }
}

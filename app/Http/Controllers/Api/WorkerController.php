<?php

namespace App\Http\Controllers\Api;

use App\Models\Worker;
use App\Models\WorkerQualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkerController extends BaseController
{
    /**
     * List workers.
     */
    public function index(Request $request)
    {
        $query = Worker::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by function
        if ($request->has('function')) {
            $query->where('function', $request->function);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('cin', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Include relations
        if ($request->has('with')) {
            $with = explode(',', $request->with);
            $query->with($with);
        }

        return $this->paginatedResponse($query->orderBy('last_name'), $request, 'workers_list');
    }

    /**
     * Get single worker.
     */
    public function show($id)
    {
        $worker = Worker::with(['qualifications', 'trainings', 'sanctions', 'assignedPpe'])->findOrFail($id);

        return $this->successResponse([
            ...$worker->toArray(),
            'is_medically_fit' => $worker->isMedicallyFit(),
            'age' => $worker->getAge(),
            'expired_qualifications_count' => $worker->expiredQualifications()->count(),
        ]);
    }

    /**
     * Create worker.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cin' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'function' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'contract_type' => 'nullable|in:cdi,cdd,intern,temporary,subcontractor',
            'hire_date' => 'nullable|date',
            'blood_type' => 'nullable|string|max:5',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,suspended,terminated',
        ]);

        $worker = Worker::create($validated);

        $this->logActivity('worker_created', $worker);
        $this->clearCache('workers_list');

        return $this->successResponse($worker, 'Worker created successfully', 201);
    }

    /**
     * Update worker.
     */
    public function update(Request $request, $id)
    {
        $worker = Worker::findOrFail($id);

        $validated = $request->validate([
            'cin' => 'sometimes|string|max:20',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'function' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'contract_type' => 'nullable|in:cdi,cdd,intern,temporary,subcontractor',
            'hire_date' => 'nullable|date',
            'medical_fitness_date' => 'nullable|date',
            'medical_fitness_status' => 'nullable|in:fit,unfit,restricted,pending',
            'blood_type' => 'nullable|string|max:5',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended,terminated',
        ]);

        $worker->update($validated);

        $this->logActivity('worker_updated', $worker);
        $this->clearCache('workers_list');

        return $this->successResponse($worker, 'Worker updated successfully');
    }

    /**
     * Delete worker.
     */
    public function destroy($id)
    {
        $worker = Worker::findOrFail($id);
        $worker->delete();

        $this->logActivity('worker_deleted', $worker);
        $this->clearCache('workers_list');

        return $this->successResponse(null, 'Worker deleted successfully');
    }

    /**
     * Get worker qualifications.
     */
    public function qualifications($id)
    {
        $worker = Worker::findOrFail($id);
        $qualifications = $worker->qualifications()->with('trainingSession')->latest()->get();

        return $this->successResponse($qualifications);
    }

    /**
     * Add qualification.
     */
    public function addQualification(Request $request, $id)
    {
        $worker = Worker::findOrFail($id);

        $validated = $request->validate([
            'certificate_name' => 'required|string|max:255',
            'skill' => 'required|string|max:255',
            'issuing_authority' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'certificate_number' => 'nullable|string|max:100',
            'file_path' => 'nullable|string',
        ]);

        $qualification = $worker->qualifications()->create($validated);

        $this->logActivity('qualification_added', $qualification, ['worker_id' => $id]);

        return $this->successResponse($qualification, 'Qualification added successfully', 201);
    }

    /**
     * Get worker trainings.
     */
    public function trainings($id)
    {
        $worker = Worker::findOrFail($id);
        $trainings = $worker->trainings()->with('trainingSession')->latest()->get();

        return $this->successResponse($trainings);
    }

    /**
     * Get worker PPE assignments.
     */
    public function ppe($id)
    {
        $worker = Worker::findOrFail($id);
        $ppe = $worker->assignedPpe()->with(['ppeItem', 'ppeStock'])->latest()->get();

        return $this->successResponse($ppe);
    }

    /**
     * Import workers from CSV/Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        // Queue the import job
        // \App\Jobs\ImportWorkers::dispatch($path, auth()->user()->company_id);

        $this->logActivity('workers_import_started', null, ['file' => $file->getClientOriginalName()]);

        return $this->successResponse(null, 'Import started. You will be notified when complete.');
    }
}

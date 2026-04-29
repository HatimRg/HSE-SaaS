<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends BaseController
{
    /**
     * List projects.
     */
    public function index(Request $request)
    {
        $query = Project::with(['manager', 'company']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter active only
        if ($request->boolean('active_only')) {
            $query->whereIn('status', ['new', 'active']);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        return $this->paginatedResponse($query->latest(), $request, 'projects_list');
    }

    /**
     * Get single project.
     */
    public function show($id)
    {
        $project = Project::with(['manager', 'team', 'company'])->findOrFail($id);

        return $this->successResponse([
            ...$project->toArray(),
            'is_active' => $project->isActive(),
            'duration_days' => $project->getDuration(),
            'stats' => $this->getProjectStats($project),
        ]);
    }

    /**
     * Create project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:projects',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'in:new,active,completed,suspended,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $project = Project::create($validated);

        $this->logActivity('project_created', $project);
        $this->clearCache('projects_list');

        return $this->successResponse($project, 'Project created successfully', 201);
    }

    /**
     * Update project.
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:projects,code,' . $id,
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:new,active,completed,suspended,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $project->update($validated);

        $this->logActivity('project_updated', $project);
        $this->clearCache('projects_list');

        return $this->successResponse($project, 'Project updated successfully');
    }

    /**
     * Delete project.
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        $this->logActivity('project_deleted', $project);
        $this->clearCache('projects_list');

        return $this->successResponse(null, 'Project deleted successfully');
    }

    /**
     * Get project team.
     */
    public function team($id)
    {
        $project = Project::findOrFail($id);
        $team = $project->team()->withPivot('role_in_project')->get();

        return $this->successResponse($team);
    }

    /**
     * Add team member.
     */
    public function addTeamMember(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_project' => 'nullable|string|max:100',
        ]);

        if (!$project->team->contains($validated['user_id'])) {
            $project->team()->attach($validated['user_id'], [
                'role_in_project' => $validated['role_in_project'] ?? null,
            ]);

            $this->logActivity('team_member_added', $project, ['user_id' => $validated['user_id']]);
        }

        return $this->successResponse(null, 'Team member added successfully');
    }

    /**
     * Remove team member.
     */
    public function removeTeamMember($id, $userId)
    {
        $project = Project::findOrFail($id);
        $project->team()->detach($userId);

        $this->logActivity('team_member_removed', $project, ['user_id' => $userId]);

        return $this->successResponse(null, 'Team member removed successfully');
    }

    /**
     * Get project statistics.
     */
    public function stats($id)
    {
        $project = Project::findOrFail($id);

        return $this->successResponse($this->getProjectStats($project));
    }

    /**
     * Calculate project statistics.
     */
    private function getProjectStats(Project $project): array
    {
        return [
            'workers_count' => \App\Models\DailyHeadcount::where('project_id', $project->id)
                ->whereBetween('date', [now()->subMonth(), now()])
                ->avg('total_count') ?? 0,
            'kpi_reports_count' => $project->kpiReports()->count(),
            'open_sors_count' => $project->sorReports()->whereIn('status', ['open', 'in-progress'])->count(),
            'active_permits_count' => $project->workPermits()
                ->where('status', 'approved')
                ->where('expiry_date', '>', now())
                ->count(),
            'inspections_count' => $project->inspections()->count(),
            'trainings_count' => $project->trainingSessions()->count(),
            'machines_count' => $project->machines()->count(),
        ];
    }
}

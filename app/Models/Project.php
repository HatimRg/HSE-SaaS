<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends BaseModel
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'location',
        'client_name',
        'start_date',
        'end_date',
        'status',
        'budget',
        'manager_id',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'settings' => 'array',
    ];

    /**
     * Get the company that owns the project.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the project manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get team members.
     */
    public function team(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_teams')
                    ->withTimestamps()
                    ->withPivot('role_in_project');
    }

    /**
     * Get KPI reports.
     */
    public function kpiReports(): HasMany
    {
        return $this->hasMany(KpiReport::class);
    }

    /**
     * Get SOR reports.
     */
    public function sorReports(): HasMany
    {
        return $this->hasMany(SorReport::class);
    }

    /**
     * Get work permits.
     */
    public function workPermits(): HasMany
    {
        return $this->hasMany(WorkPermit::class);
    }

    /**
     * Get inspections.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Get machines.
     */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    /**
     * Get training sessions.
     */
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * Get PPE stock.
     */
    public function ppeStock(): HasMany
    {
        return $this->hasMany(PpeStock::class);
    }

    /**
     * Get waste exports.
     */
    public function wasteExports(): HasMany
    {
        return $this->hasMany(WasteExport::class);
    }

    /**
     * Get daily headcounts.
     */
    public function dailyHeadcounts(): HasMany
    {
        return $this->hasMany(DailyHeadcount::class);
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get project duration in days.
     */
    public function getDuration(): int
    {
        if (!$this->start_date) {
            return 0;
        }
        
        $end = $this->end_date ?? now();
        return $this->start_date->diffInDays($end);
    }

    /**
     * Scope: Active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}

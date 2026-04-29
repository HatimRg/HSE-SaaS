<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiReport extends BaseModel
{
    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'period_start',
        'period_end',
        'status',
        'total_hours',
        'injuries',
        'first_aids',
        'near_misses',
        'observations',
        'lost_time_incidents',
        'environmental_incidents',
        'vehicles_damaged',
        'vehicles_lost',
        'manpower_count',
        'remarks',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'total_hours' => 'decimal:2',
    ];

    protected $encrypted = ['remarks'];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get daily snapshots.
     */
    public function dailySnapshots(): HasMany
    {
        return $this->hasMany(DailyKpiSnapshot::class);
    }

    /**
     * Get monthly measurements.
     */
    public function monthlyMeasurements(): HasMany
    {
        return $this->hasMany(MonthlyKpiMeasurement::class);
    }

    /**
     * Calculate TRIR (Total Recordable Incident Rate).
     */
    public function calculateTrir(): float
    {
        if ($this->total_hours <= 0) {
            return 0;
        }
        
        return ($this->injuries * 200000) / $this->total_hours;
    }

    /**
     * Calculate severity rate.
     */
    public function calculateSeverityRate(): float
    {
        if ($this->total_hours <= 0) {
            return 0;
        }
        
        return ($this->lost_time_incidents * 1000) / $this->total_hours;
    }

    /**
     * Calculate frequency rate.
     */
    public function calculateFrequencyRate(): float
    {
        if ($this->total_hours <= 0) {
            return 0;
        }
        
        return ($this->injuries * 1000000) / $this->total_hours;
    }

    /**
     * Check if report can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Scope: By status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope: For period.
     */
    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('period_start', [$start, $end])
                     ->orWhereBetween('period_end', [$start, $end]);
    }
}

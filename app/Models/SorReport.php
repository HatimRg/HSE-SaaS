<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SorReport extends BaseModel
{
    protected $table = 'sor_reports';

    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'reference',
        'date',
        'title',
        'description',
        'type',
        'severity',
        'status',
        'location',
        'responsible_person_id',
        'corrective_action',
        'due_date',
        'completed_at',
        'photos',
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'photos' => 'array',
        'attachments' => 'array',
    ];

    protected $encrypted = ['description', 'corrective_action'];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the reporter.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the responsible person.
     */
    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /**
     * Get comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(SorComment::class);
    }

    /**
     * Generate unique reference.
     */
    public static function generateReference(): string
    {
        $prefix = 'SOR-' . now()->format('Y');
        $count = self::whereYear('created_at', now()->year)->count() + 1;
        return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'closed' && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Scope: By type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: By severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Open items.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in-progress']);
    }

    /**
     * Scope: Overdue items.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'closed')
                     ->where('due_date', '<', now());
    }
}

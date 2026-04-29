<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends BaseModel
{
    protected $table = 'training_sessions';

    protected $fillable = [
        'company_id',
        'project_id',
        'title',
        'description',
        'type',
        'category',
        'trainer_name',
        'trainer_id',
        'start_date',
        'end_date',
        'duration_hours',
        'location',
        'max_participants',
        'status',
        'materials_path',
        'certificate_template',
        'prerequisites',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_hours' => 'decimal:2',
        'prerequisites' => 'array',
    ];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the trainer.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get participants.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'worker_trainings')
                    ->withPivot(['attended', 'score', 'certificate_issued', 'certificate_number', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get awareness records.
     */
    public function awarenessRecords(): HasMany
    {
        return $this->hasMany(AwarenessSession::class);
    }

    /**
     * Check if session is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_date && $this->start_date->isFuture();
    }

    /**
     * Check if session is ongoing.
     */
    public function isOngoing(): bool
    {
        $now = now();
        return $this->start_date && 
               $this->end_date &&
               $this->start_date <= $now && 
               $this->end_date >= $now;
    }

    /**
     * Check if session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Get available capacity.
     */
    public function getAvailableCapacity(): int
    {
        $enrolled = $this->participants()->count();
        return max(0, $this->max_participants - $enrolled);
    }

    /**
     * Get training types.
     */
    public static function getTypes(): array
    {
        return [
            'induction' => 'Safety Induction',
            'toolbox_talk' => 'Toolbox Talk',
            'hse_awareness' => 'HSE Awareness',
            'skill' => 'Skill Training',
            'certification' => 'Certification Course',
            'emergency' => 'Emergency Response',
            'first_aid' => 'First Aid',
            'fire_safety' => 'Fire Safety',
        ];
    }

    /**
     * Scope: Upcoming sessions.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * Scope: Active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

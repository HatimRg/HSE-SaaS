<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPermit extends BaseModel
{
    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'permit_number',
        'type',
        'title',
        'description',
        'location',
        'issued_date',
        'expiry_date',
        'status',
        'hazards_identified',
        'precautions_taken',
        'fire_watch_required',
        'fire_watch_assigned_to',
        'issuing_authority_id',
        'approver_id',
        'approved_at',
        'suspended_at',
        'suspension_reason',
        'renewal_of',
        'attachments',
    ];

    protected $casts = [
        'issued_date' => 'datetime',
        'expiry_date' => 'datetime',
        'approved_at' => 'datetime',
        'suspended_at' => 'datetime',
        'fire_watch_required' => 'boolean',
        'attachments' => 'array',
    ];

    protected $encrypted = ['hazards_identified', 'precautions_taken', 'suspension_reason'];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the requester.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the issuing authority.
     */
    public function issuingAuthority(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuing_authority_id');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the fire watch assignee.
     */
    public function fireWatchAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fire_watch_assigned_to');
    }

    /**
     * Get the original permit if this is a renewal.
     */
    public function originalPermit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewal_of');
    }

    /**
     * Generate unique permit number.
     */
    public static function generatePermitNumber(): string
    {
        $prefix = 'WP-' . now()->format('Y');
        $count = self::whereYear('created_at', now()->year)->count() + 1;
        return $prefix . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if permit is valid.
     */
    public function isValid(): bool
    {
        return $this->status === 'approved' &&
               $this->expiry_date &&
               $this->expiry_date->isFuture();
    }

    /**
     * Check if permit is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if permit expires soon (within 24 hours).
     */
    public function expiresSoon(): bool
    {
        return $this->status === 'approved' &&
               $this->expiry_date &&
               $this->expiry_date->diffInHours(now()) <= 24 &&
               $this->expiry_date->isFuture();
    }

    /**
     * Get available permit types.
     */
    public static function getTypes(): array
    {
        return [
            'hot_work' => 'Hot Work (Feu)',
            'working_at_height' => 'Working at Height (Hauteur)',
            'confined_space' => 'Confined Space (Espace Confiné)',
            'electrical' => 'Electrical Work (Électrique)',
            'excavation' => 'Excavation (Terrassement)',
            'demolition' => 'Demolition (Démolition)',
            'other' => 'Other (Autre)',
        ];
    }

    /**
     * Scope: Valid permits.
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'approved')
                     ->where('expiry_date', '>', now());
    }

    /**
     * Scope: Expired permits.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope: By type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

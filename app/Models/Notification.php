<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends BaseModel
{
    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'message',
        'type',
        'urgency',
        'action_url',
        'action_text',
        'read_at',
        'dedupe_key',
        'data',
        'sent_via',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array',
        'sent_via' => 'array',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if urgent.
     */
    public function isUrgent(): bool
    {
        return in_array($this->urgency, ['urgent', 'critical']);
    }

    /**
     * Get urgency levels.
     */
    public static function getUrgencyLevels(): array
    {
        return [
            'info' => 'Information',
            'warning' => 'Warning',
            'urgent' => 'Urgent',
            'critical' => 'Critical',
        ];
    }

    /**
     * Get notification types.
     */
    public static function getTypes(): array
    {
        return [
            'system' => 'System',
            'kpi' => 'KPI Report',
            'sor' => 'Safety Observation',
            'permit' => 'Work Permit',
            'inspection' => 'Inspection',
            'training' => 'Training',
            'worker' => 'Worker',
            'approval' => 'Approval Required',
        ];
    }

    /**
     * Scope: Unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: By urgency.
     */
    public function scopeByUrgency($query, string $urgency)
    {
        return $query->where('urgency', $urgency);
    }

    /**
     * Scope: Recent notifications.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

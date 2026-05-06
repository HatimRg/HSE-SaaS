<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EventAction extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'priority', 'status', 'assigned_to', 'due_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Event action has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'source_type', 'source_id',
        'description', 'type', 'priority', 'status',
        'assigned_to', 'due_date', 'completed_at', 'verified_at', 'verified_by',
        'notes', 'attachments',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

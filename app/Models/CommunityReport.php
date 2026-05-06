<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CommunityReport extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'severity', 'status', 'assigned_to'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Community report has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'project_id', 'type', 'severity', 'status',
        'reporter_name', 'reporter_contact', 'reporter_organization',
        'description', 'location', 'reported_at',
        'assigned_to', 'resolution', 'resolved_at', 'attachments',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

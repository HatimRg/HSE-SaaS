<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkerProjectAssignment extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'assigned_from', 'assigned_until'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Worker project assignment has been {$eventName}");
    }
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'worker_id', 'project_id',
        'assigned_from', 'assigned_until', 'status',
    ];

    protected $casts = [
        'assigned_from' => 'date',
        'assigned_until' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

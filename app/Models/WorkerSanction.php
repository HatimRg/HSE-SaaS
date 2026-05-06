<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkerSanction extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'severity', 'status', 'reason'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Worker sanction has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'worker_id', 'project_id', 'type', 'severity', 'reason',
        'issued_by', 'issued_at', 'suspension_from', 'suspension_until',
        'corrective_action', 'status',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'suspension_from' => 'date',
        'suspension_until' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}

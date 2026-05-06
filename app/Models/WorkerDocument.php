<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkerDocument extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'name', 'status', 'expiry_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Worker document has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'worker_id', 'type', 'name', 'description',
        'issuer', 'issue_date', 'expiry_date', 'status',
        'file_path', 'metadata', 'training_session_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }
}

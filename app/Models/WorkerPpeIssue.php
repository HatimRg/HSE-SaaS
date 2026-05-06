<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkerPpeIssue extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'issued_at', 'returned_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "PPE issue has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'worker_id', 'ppe_item_id', 'project_id',
        'quantity', 'size', 'issued_at', 'expected_return_date', 'returned_at',
        'condition_on_return', 'issued_by', 'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expected_return_date' => 'date',
        'returned_at' => 'date',
        'quantity' => 'integer',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function ppeItem(): BelongsTo
    {
        return $this->belongsTo(PpeItem::class);
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

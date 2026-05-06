<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class HseEvent extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'severity', 'status', 'title', 'assigned_to', 'due_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "HSE Event {$this->reference} has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'project_id', 'reported_by', 'reference',
        'type', 'severity', 'status', 'title', 'description', 'location',
        'risk_item_id', 'assigned_to', 'due_date', 'closed_at', 'verified_at',
        'escalation_level', 'occurred_at', 'photos', 'attachments',
    ];

    protected array $encrypted = ['description'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'due_date' => 'date',
        'closed_at' => 'datetime',
        'verified_at' => 'datetime',
        'escalation_level' => 'integer',
        'photos' => 'array',
        'attachments' => 'array',
    ];

    public static function generateReference(): string
    {
        $prefix = 'HSE-' . now()->format('Y');
        $count = self::whereYear('created_at', now()->year)->count() + 1;
        return $prefix . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function riskItem(): BelongsTo
    {
        return $this->belongsTo(RiskItem::class, 'risk_item_id');
    }

    public function actions(): MorphMany
    {
        return $this->morphMany(EventAction::class, 'source');
    }
}

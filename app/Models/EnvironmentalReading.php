<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EnvironmentalReading extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'value', 'is_exceedance'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Environmental reading has been {$eventName}");
    }
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'project_id', 'type', 'value', 'unit',
        'threshold_min', 'threshold_max', 'is_exceedance',
        'location', 'measured_at', 'measured_by', 'notes', 'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'threshold_min' => 'decimal:4',
        'threshold_max' => 'decimal:4',
        'is_exceedance' => 'boolean',
        'measured_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function measuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}

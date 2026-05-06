<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KpiValue extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['value', 'target_value', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "KPI value has been {$eventName}");
    }
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'kpi_definition_id', 'project_id',
        'period_start', 'period_end', 'value', 'target_value', 'status',
        'input_snapshot', 'computed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'input_snapshot' => 'array',
        'computed_at' => 'datetime',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_definition_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

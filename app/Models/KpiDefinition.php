<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KpiDefinition extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'formula', 'target_value', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "KPI definition {$this->name} has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'name', 'code', 'description', 'formula', 'input_mapping',
        'frequency', 'unit', 'target_value', 'alert_threshold', 'direction',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'input_mapping' => 'array',
        'target_value' => 'decimal:4',
        'alert_threshold' => 'decimal:4',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(KpiValue::class);
    }
}

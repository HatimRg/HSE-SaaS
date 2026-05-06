<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WasteExport extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['waste_type', 'quantity', 'treatment', 'is_hazardous'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Waste export has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'project_id', 'date', 'waste_type', 'quantity', 'unit',
        'transport_method', 'treatment_facility', 'treatment', 'is_hazardous',
        'carrier_name', 'manifest_number', 'recorded_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
        'is_hazardous' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

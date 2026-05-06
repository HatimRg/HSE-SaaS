<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PermitType extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Permit type has been {$eventName}");
    }
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'name', 'code', 'description',
        'required_safety_measures', 'required_ppe',
        'requires_fire_watch', 'is_active',
    ];

    protected $casts = [
        'required_safety_measures' => 'array',
        'required_ppe' => 'array',
        'requires_fire_watch' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(PermitTypeAssignment::class);
    }
}

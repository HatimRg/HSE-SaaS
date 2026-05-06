<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Hazard extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'category', 'description'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Hazard has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'name', 'category', 'description', 'default_control_measures',
    ];

    protected $casts = [
        'default_control_measures' => 'array',
    ];

    public function riskItems(): HasMany
    {
        return $this->hasMany(RiskItem::class);
    }
}

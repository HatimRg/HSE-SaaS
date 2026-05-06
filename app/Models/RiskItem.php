<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RiskItem extends BaseModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['risk_level_before', 'risk_level_after', 'control_measures'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Risk item has been {$eventName}");
    }
    protected $fillable = [
        'company_id', 'risk_assessment_id', 'hazard_id',
        'hazard_description', 'potential_consequence',
        'likelihood_before', 'severity_before', 'risk_score_before', 'risk_level_before',
        'control_measures', 'control_type',
        'likelihood_after', 'severity_after', 'risk_score_after', 'risk_level_after',
        'responsible_person_id', 'target_date',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class);
    }

    public function hazard(): BelongsTo
    {
        return $this->belongsTo(Hazard::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }
}

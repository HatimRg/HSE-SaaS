<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'project_id',
        'title',
        'description',
        'category',
        'hazard_type',
        'severity',
        'likelihood',
        'risk_level',
        'risk_score',
        'affected_area',
        'existing_controls',
        'recommended_actions',
        'assessor_id',
        'assessment_date',
        'review_date',
        'status',
        'priority',
        'mitigation_status',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'review_date' => 'date',
        'existing_controls' => 'array',
        'recommended_actions' => 'array',
        'risk_score' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function mitigationMeasures()
    {
        return $this->hasMany(RiskMitigation::class);
    }

    public function calculateRiskLevel(): string
    {
        $score = $this->severity * $this->likelihood;
        $this->risk_score = $score;

        if ($score >= 20) {
            return 'critical';
        } elseif ($score >= 12) {
            return 'high';
        } elseif ($score >= 6) {
            return 'medium';
        }
        return 'low';
    }
}

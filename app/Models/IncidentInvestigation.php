<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentInvestigation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'incident_id',
        'project_id',
        'investigator_id',
        'title',
        'description',
        'incident_date',
        'investigation_start_date',
        'investigation_end_date',
        'status',
        'severity',
        'root_cause_type',
        'root_cause_description',
        'immediate_actions',
        'corrective_actions',
        'preventive_actions',
        'lessons_learned',
        'witnesses',
        'attachments',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'investigation_start_date' => 'date',
        'investigation_end_date' => 'date',
        'immediate_actions' => 'array',
        'corrective_actions' => 'array',
        'preventive_actions' => 'array',
        'witnesses' => 'array',
        'attachments' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function incident()
    {
        return $this->belongsTo(SorReport::class, 'incident_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function investigator()
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }

    public function rootCauses()
    {
        return $this->hasMany(IncidentRootCause::class);
    }

    public function correctiveActions()
    {
        return $this->hasMany(IncidentCorrectiveAction::class);
    }
}

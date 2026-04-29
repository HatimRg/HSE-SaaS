<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends BaseModel
{
    protected $fillable = [
        'company_id',
        'project_id',
        'name',
        'type',
        'model',
        'manufacturer',
        'serial_number',
        'asset_tag',
        'purchase_date',
        'operator_id',
        'status',
        'location',
        'last_inspection_date',
        'next_inspection_date',
        'inspection_frequency_days',
        'notes',
        'documents',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'last_inspection_date' => 'date',
        'next_inspection_date' => 'date',
        'inspection_frequency_days' => 'integer',
        'documents' => 'array',
    ];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the operator.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Check if inspection is due.
     */
    public function isInspectionDue(): bool
    {
        if (!$this->next_inspection_date) {
            return true;
        }
        return $this->next_inspection_date->isToday() || 
               $this->next_inspection_date->isPast();
    }

    /**
     * Check if inspection is upcoming.
     */
    public function isInspectionUpcoming(int $days = 7): bool
    {
        if (!$this->next_inspection_date) {
            return false;
        }
        return $this->next_inspection_date->isFuture() &&
               $this->next_inspection_date->diffInDays(now()) <= $days;
    }

    /**
     * Update next inspection date.
     */
    public function updateNextInspection(): void
    {
        $this->last_inspection_date = now();
        $this->next_inspection_date = now()->addDays($this->inspection_frequency_days ?? 30);
        $this->save();
    }

    /**
     * Get machine types.
     */
    public static function getTypes(): array
    {
        return [
            'crane' => 'Crane (Grue)',
            'forklift' => 'Forklift (Chariot Élévateur)',
            'excavator' => 'Excavator (Pelle)',
            'bulldozer' => 'Bulldozer',
            'loader' => 'Loader (Chargeuse)',
            'concrete_mixer' => 'Concrete Mixer (Bétonnière)',
            'generator' => 'Generator (Groupe Électrogène)',
            'compressor' => 'Air Compressor',
            'welding' => 'Welding Equipment',
            'other' => 'Other',
        ];
    }

    /**
     * Scope: Inspection due.
     */
    public function scopeInspectionDue($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_inspection_date')
              ->orWhere('next_inspection_date', '<=', now());
        });
    }

    /**
     * Scope: Active machines.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

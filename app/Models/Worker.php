<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends BaseModel
{
    protected $fillable = [
        'company_id',
        'cin',
        'first_name',
        'last_name',
        'full_name',
        'date_of_birth',
        'gender',
        'nationality',
        'phone',
        'email',
        'address',
        'function',
        'department',
        'contract_type',
        'hire_date',
        'medical_fitness_date',
        'medical_fitness_status',
        'medical_notes',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'photo',
        'badges',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'medical_fitness_date' => 'date',
        'badges' => 'array',
    ];

    protected $encrypted = ['address', 'medical_notes', 'emergency_contact_phone'];

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get worker qualifications.
     */
    public function qualifications(): HasMany
    {
        return $this->hasMany(WorkerQualification::class);
    }

    /**
     * Get worker sanctions.
     */
    public function sanctions(): HasMany
    {
        return $this->hasMany(WorkerSanction::class);
    }

    /**
     * Get worker trainings.
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(WorkerTraining::class);
    }

    /**
     * Get assigned PPE.
     */
    public function assignedPpe(): HasMany
    {
        return $this->hasMany(PpeAssignment::class);
    }

    /**
     * Get full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get display name.
     */
    public function getDisplayName(): string
    {
        return $this->full_name ?? $this->cin;
    }

    /**
     * Check if medical fitness is valid.
     */
    public function isMedicallyFit(): bool
    {
        if (!$this->medical_fitness_date) {
            return false;
        }

        // Medical fitness valid for 1 year
        return $this->medical_fitness_date->addYear()->isFuture() &&
               $this->medical_fitness_status === 'fit';
    }

    /**
     * Get age.
     */
    public function getAge(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Check if has valid qualification for a skill.
     */
    public function hasValidQualification(string $skill): bool
    {
        return $this->qualifications()
                    ->where('skill', $skill)
                    ->where('expiry_date', '>', now())
                    ->exists();
    }

    /**
     * Get all expired qualifications.
     */
    public function expiredQualifications(): HasMany
    {
        return $this->qualifications()
                    ->where('expiry_date', '<', now());
    }

    /**
     * Scope: Active workers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By function.
     */
    public function scopeByFunction($query, string $function)
    {
        return $query->where('function', $function);
    }

    /**
     * Scope: Medical fitness expiring soon.
     */
    public function scopeMedicalExpiringSoon($query, int $days = 30)
    {
        return $query->where('medical_fitness_date', '<=', now()->subYear()->addDays($days))
                     ->where('medical_fitness_date', '>', now()->subYear());
    }

    /**
     * Get contract types.
     */
    public static function getContractTypes(): array
    {
        return [
            'cdi' => 'CDI (Contrat à Durée Indéterminée)',
            'cdd' => 'CDD (Contrat à Durée Déterminée)',
            'intern' => 'Stagiaire',
            'temporary' => 'Intérimaire',
            'subcontractor' => 'Sous-traitant',
        ];
    }
}

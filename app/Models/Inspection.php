<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends BaseModel
{
    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'reference',
        'date',
        'type',
        'location',
        'inspector_name',
        'result',
        'score',
        'checklist',
        'findings',
        'recommendations',
        'corrective_actions',
        'next_inspection_date',
        'status',
        'report_path',
        'photos',
    ];

    protected $casts = [
        'date' => 'date',
        'next_inspection_date' => 'date',
        'score' => 'decimal:2',
        'checklist' => 'array',
        'photos' => 'array',
    ];

    protected $encrypted = ['findings', 'recommendations', 'corrective_actions'];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the inspector.
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get inspection items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class);
    }

    /**
     * Generate unique reference.
     */
    public static function generateReference(): string
    {
        $prefix = 'INS-' . now()->format('Y');
        $count = self::whereYear('created_at', now()->year)->count() + 1;
        return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get inspection types.
     */
    public static function getTypes(): array
    {
        return [
            'safety' => 'Safety Inspection',
            'environmental' => 'Environmental Inspection',
            'equipment' => 'Equipment Inspection',
            'housekeeping' => 'Housekeeping Inspection',
            'ppe' => 'PPE Inspection',
            'fire' => 'Fire Safety Inspection',
            'electrical' => 'Electrical Inspection',
        ];
    }

    /**
     * Check if inspection passed.
     */
    public function passed(): bool
    {
        return $this->result === 'pass';
    }

    /**
     * Scope: By result.
     */
    public function scopeByResult($query, string $result)
    {
        return $query->where('result', $result);
    }

    /**
     * Scope: By type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Failed inspections.
     */
    public function scopeFailed($query)
    {
        return $query->where('result', 'fail');
    }

    /**
     * Scope: Upcoming inspections.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('next_inspection_date', '>=', now())
                     ->where('next_inspection_date', '<=', now()->addDays(7));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PpeItem extends BaseModel
{
    protected $table = 'ppe_items';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'category',
        'size_options',
        'color_options',
        'supplier',
        'unit_cost',
        'reorder_level',
        'specifications',
        'certifications',
        'shelf_life_months',
        'is_active',
    ];

    protected $casts = [
        'size_options' => 'array',
        'color_options' => 'array',
        'unit_cost' => 'decimal:2',
        'reorder_level' => 'integer',
        'specifications' => 'array',
        'certifications' => 'array',
        'shelf_life_months' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get stock records.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(PpeStock::class, 'ppe_item_id');
    }

    /**
     * Get assignments.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PpeAssignment::class, 'ppe_item_id');
    }

    /**
     * Get total stock across all projects.
     */
    public function getTotalStock(): int
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Check if stock is low.
     */
    public function isStockLow(): bool
    {
        return $this->getTotalStock() <= $this->reorder_level;
    }

    /**
     * Get PPE categories.
     */
    public static function getCategories(): array
    {
        return [
            'head' => 'Head Protection (Tête)',
            'eye_face' => 'Eye & Face Protection (Visage)',
            'hearing' => 'Hearing Protection (Auditive)',
            'respiratory' => 'Respiratory Protection (Respiratoire)',
            'hand' => 'Hand Protection (Mains)',
            'foot' => 'Foot Protection (Pieds)',
            'body' => 'Body Protection (Corps)',
            'fall_protection' => 'Fall Protection (Chute)',
            'high_visibility' => 'High Visibility (Haute Visibilité)',
        ];
    }

    /**
     * Scope: By category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->selectRaw('ppe_item_id, SUM(quantity) as total')
              ->groupBy('ppe_item_id')
              ->havingRaw('total <= reorder_level');
        });
    }
}

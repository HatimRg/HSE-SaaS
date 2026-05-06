<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpeStock extends BaseModel
{
    protected $table = 'ppe_stocks';

    protected $fillable = [
        'company_id',
        'project_id',
        'ppe_item_id',
        'quantity',
        'min_stock_level',
        'storage_location',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'min_stock_level' => 'integer',
    ];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the PPE item.
     */
    public function ppeItem(): BelongsTo
    {
        return $this->belongsTo(PpeItem::class, 'ppe_item_id');
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock_level;
    }

    public function scopeByItem($query, int $ppeItemId)
    {
        return $query->where('ppe_item_id', $ppeItemId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_stock_level');
    }
}

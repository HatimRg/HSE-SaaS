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
        'location',
        'batch_number',
        'expiry_date',
        'received_date',
        'supplier',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_date' => 'date',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
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

    /**
     * Adjust stock quantity.
     */
    public function adjustQuantity(int $amount, string $reason = ''): bool
    {
        $newQuantity = $this->quantity + $amount;
        
        if ($newQuantity < 0) {
            return false;
        }

        $this->quantity = $newQuantity;
        
        // Log the adjustment
        \App\Models\StockAdjustment::create([
            'company_id' => $this->company_id,
            'ppe_stock_id' => $this->id,
            'amount' => $amount,
            'reason' => $reason,
            'previous_quantity' => $this->quantity - $amount,
            'new_quantity' => $newQuantity,
        ]);

        return $this->save();
    }

    /**
     * Check if stock is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date &&
               $this->expiry_date->isFuture() &&
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Scope: By PPE item.
     */
    public function scopeByItem($query, int $ppeItemId)
    {
        return $query->where('ppe_item_id', $ppeItemId);
    }

    /**
     * Scope: Expired stock.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope: Expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateItem extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'inspection_template_id', 'description', 'category', 'is_required',
        'weight', 'guidance_notes', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'weight' => 'integer',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class);
    }
}

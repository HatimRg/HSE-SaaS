<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionItem extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'inspection_id', 'template_item_id', 'checklist_item',
        'status', 'severity', 'note', 'photos',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(TemplateItem::class);
    }
}

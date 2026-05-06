<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitTypeAssignment extends BaseModel
{
    public $timestamps = false;

    protected $fillable = ['permit_id', 'permit_type_id'];

    public function permit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class, 'permit_id');
    }

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }
}
